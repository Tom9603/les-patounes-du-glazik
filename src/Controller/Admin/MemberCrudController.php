<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Repository\BookingRepository;
use App\Service\AuditLogger;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class MemberCrudController extends AbstractCrudController
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private AuditLogger $auditLogger,
        private AdminUrlGenerator $adminUrlGenerator,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function delete(AdminContext $context): KeyValueStore|Response
    {
        /** @var Member $entity */
        $entity = $context->getEntity()->getInstance();

        if ($this->bookingRepo->hasActivBookings($entity)) {
            $this->addFlash('danger', sprintf(
                'Impossible de supprimer %s : des réservations actives (En attente ou Confirmées) sont encore en cours. Annulez-les avant de supprimer ce compte.',
                $entity->getEmail()
            ));

            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl()
            );
        }

        return parent::delete($context);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des utilisateurs')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('firstName')->setLabel('Prénom');
        yield TextField::new('lastName')->setLabel('Nom')->setRequired(false);
        yield TextField::new('username')->setLabel('Pseudo')->setRequired(false);
        yield EmailField::new('email')->setLabel('Email');
        yield ChoiceField::new('roles')
            ->setLabel('Rôle')
            ->setChoices([
                'Utilisateur' => 'ROLE_MEMBER',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded(false)
            ->onlyOnForms();
        yield TextField::new('roleLabel')
            ->setLabel('Rôle')
            ->onlyOnIndex();
        yield BooleanField::new('isVerified')
            ->setLabel('Email vérifié')
            ->renderAsSwitch(true);
        yield DateTimeField::new('createdAt')->setLabel('Inscrit le')->onlyOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(BooleanFilter::new('isVerified')->setLabel('Email vérifié'));
    }
}
