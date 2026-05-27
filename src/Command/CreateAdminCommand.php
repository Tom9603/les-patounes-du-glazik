<?php

namespace App\Command;

use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crée un compte administrateur')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email / identifiant');
        $this->addArgument('password', InputArgument::REQUIRED, 'Mot de passe');
        $this->addArgument('name', InputArgument::OPTIONAL, 'Prénom affiché');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $member = new Member();
        $member->setEmail($input->getArgument('email'));
        $member->setPassword($this->hasher->hashPassword($member, $input->getArgument('password')));
        $member->setFirstName($input->getArgument('name') ?? 'Admin');
        $member->setRoles(['ROLE_ADMIN']);
        $member->setIsVerified(true);

        $this->em->persist($member);
        $this->em->flush();

        $io->success(sprintf('Admin créé : %s', $member->getEmail()));

        return Command::SUCCESS;
    }
}
