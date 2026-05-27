<?php

namespace App\Controller;

use App\Entity\Member;
use App\Repository\MemberRepository;
use App\Service\ImageResizerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MemberController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailerInterface $mailer,
    ): Response {
        if ($this->getUser()) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin')
                : $this->redirectToRoute('app_blog');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $firstName = trim($request->request->get('firstName', ''));
            $lastName  = trim($request->request->get('lastName', ''));
            $username  = trim($request->request->get('username', ''));
            $email     = trim($request->request->get('email', ''));
            $password  = $request->request->get('password', '');
            $confirm   = $request->request->get('confirm', '');

            if (!$firstName || !$lastName || !$username || !$email || !$password) {
                $error = 'Tous les champs obligatoires doivent être remplis.';
            } elseif (strlen($username) < 3) {
                $error = 'Le pseudo doit contenir au moins 3 caractères.';
            } elseif (!preg_match('/^[\w\-]+$/u', $username)) {
                $error = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.';
            } elseif ($em->getRepository(Member::class)->findOneBy(['username' => $username])) {
                $error = 'Ce pseudo est déjà utilisé.';
            } elseif ($password !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($em->getRepository(Member::class)->findOneBy(['email' => $email])) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $member = new Member();
                $member->setFirstName($firstName);
                $member->setLastName($lastName);
                $member->setUsername($username);
                $member->setEmail($email);
                $member->setPassword($hasher->hashPassword($member, $password));

                $em->persist($member);
                $em->flush();

                $verifyUrl = $this->generateUrl('app_verify_email', [
                    'token' => $member->getVerificationToken(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $verificationEmail = (new TemplatedEmail())
                    ->from($_ENV['MAILER_FROM'] ?? 'noreply@lespatounesduglaizik.fr')
                    ->to($member->getEmail())
                    ->subject('Confirmez votre inscription - Les patounes du glazik')
                    ->htmlTemplate('emails/verify.html.twig')
                    ->context([
                        'member'    => $member,
                        'verifyUrl' => $verifyUrl,
                    ]);

                $mailer->send($verificationEmail);

                return $this->render('member/register_confirm.html.twig', [
                    'email' => $member->getEmail(),
                ]);
            }
        }

        return $this->render('member/register.html.twig', ['error' => $error]);
    }

    #[Route('/verification/{token}', name: 'app_verify_email')]
    public function verify(
        string $token,
        MemberRepository $memberRepo,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        $member = $memberRepo->findByToken($token);

        if (!$member) {
            $this->addFlash('error', 'Lien de vérification invalide ou déjà utilisé.');
            return $this->redirectToRoute('app_member_login');
        }

        $member->setIsVerified(true);
        $em->flush();

        $security->login($member, 'form_login', 'main');

        $this->addFlash('success', 'Votre compte est activé. Bienvenue ' . $member->getFirstName() . ' !');
        return $this->redirectToRoute('app_blog');
    }

    #[Route('/connexion', name: 'app_member_login')]
    public function login(\Symfony\Component\Security\Http\Authentication\AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin')
                : $this->redirectToRoute('app_blog');
        }

        return $this->render('member/login.html.twig', [
            'error'         => $authUtils->getLastAuthenticationError(),
            'last_username' => $authUtils->getLastUsername(),
        ]);
    }

    #[Route('/deconnexion-membre', name: 'app_member_logout')]
    public function logout(): void {}

    // ----------------------------------------------------------------
    // Profile
    // ----------------------------------------------------------------

    #[Route('/profil', name: 'app_profile', methods: ['GET'])]
    public function profile(): Response
    {
        /** @var Member $member */
        $member = $this->getUser();

        return $this->render('member/profile.html.twig', ['member' => $member]);
    }

    #[Route('/profil/info', name: 'app_profile_update', methods: ['POST'])]
    public function profileUpdateInfo(
        Request $request,
        EntityManagerInterface $em,
        ImageResizerService $resizer,
    ): Response {
        /** @var Member $member */
        $member = $this->getUser();

        if (!$this->isCsrfTokenValid('profile-info', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $firstName = trim($request->request->get('firstName', ''));
        $lastName  = trim($request->request->get('lastName', ''));
        $username  = trim($request->request->get('username', ''));
        $email     = trim($request->request->get('email', ''));

        if (!$firstName || !$email) {
            $this->addFlash('error', 'Le prénom et l\'email sont obligatoires.');
            return $this->redirectToRoute('app_profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return $this->redirectToRoute('app_profile');
        }

        if ($username !== '' && strlen($username) < 3) {
            $this->addFlash('error', 'Le pseudo doit contenir au moins 3 caractères.');
            return $this->redirectToRoute('app_profile');
        }

        if ($username !== '' && !preg_match('/^[\w\-]+$/u', $username)) {
            $this->addFlash('error', 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.');
            return $this->redirectToRoute('app_profile');
        }

        if ($username !== '' && $username !== $member->getUsername()) {
            $existing = $em->getRepository(Member::class)->findOneBy(['username' => $username]);
            if ($existing && $existing->getId() !== $member->getId()) {
                $this->addFlash('error', 'Ce pseudo est déjà utilisé.');
                return $this->redirectToRoute('app_profile');
            }
        }

        if ($email !== $member->getEmail()) {
            $existing = $em->getRepository(Member::class)->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $member->getId()) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                return $this->redirectToRoute('app_profile');
            }
        }

        $member->setFirstName($firstName);
        $member->setLastName($lastName ?: null);
        $member->setUsername($username ?: null);
        $member->setEmail($email);

        $avatarFile = $request->files->get('avatar');
        if ($avatarFile) {
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
            if (!in_array($avatarFile->getMimeType(), $allowed)) {
                $this->addFlash('error', 'Format d\'image non supporté. Utilisez JPEG, PNG, WEBP, GIF ou BMP.');
                return $this->redirectToRoute('app_profile');
            }

            if ($avatarFile->getSize() > 10 * 1024 * 1024) {
                $this->addFlash('error', 'L\'image ne doit pas dépasser 10 Mo.');
                return $this->redirectToRoute('app_profile');
            }

            $avatarDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/';
            if (!is_dir($avatarDir)) {
                mkdir($avatarDir, 0755, true);
            }

            $filename = 'avatar_' . $member->getId() . '_' . uniqid() . '.jpg';
            $destPath = $avatarDir . $filename;

            if ($resizer->resizeToSquare($avatarFile->getPathname(), $destPath)) {
                if ($member->getAvatarFilename()) {
                    @unlink($avatarDir . $member->getAvatarFilename());
                }
                $member->setAvatarFilename($filename);
            } else {
                $this->addFlash('error', 'Impossible de traiter l\'image.');
                return $this->redirectToRoute('app_profile');
            }
        }

        $em->flush();
        $this->addFlash('success', 'Vos informations ont été mises à jour.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profil/password', name: 'app_profile_password', methods: ['POST'])]
    public function profileUpdatePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        /** @var Member $member */
        $member = $this->getUser();

        if (!$this->isCsrfTokenValid('profile-password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $current  = $request->request->get('currentPassword', '');
        $new      = $request->request->get('newPassword', '');
        $confirm  = $request->request->get('confirmPassword', '');

        if (!$hasher->isPasswordValid($member, $current)) {
            $this->addFlash('error', 'Mot de passe actuel incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        if (strlen($new) < 8) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('app_profile');
        }

        if ($new !== $confirm) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_profile');
        }

        $member->setPassword($hasher->hashPassword($member, $new));
        $em->flush();

        $this->addFlash('success', 'Mot de passe modifié avec succès.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profil/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function profileDelete(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        /** @var Member $member */
        $member = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Les administrateurs ne peuvent pas supprimer leur compte depuis cette interface.');
            return $this->redirectToRoute('app_profile');
        }

        if (!$this->isCsrfTokenValid('profile-delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_profile');
        }

        if ($member->getAvatarFilename()) {
            $path = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $member->getAvatarFilename();
            @unlink($path);
        }

        $em->remove($member);
        $em->flush();

        $request->getSession()->invalidate();
        $this->addFlash('success', 'Votre compte a été supprimé.');
        return $this->redirectToRoute('app_member_login');
    }
}
