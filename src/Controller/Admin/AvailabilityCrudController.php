<?php

namespace App\Controller\Admin;

use App\Entity\Availability;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AvailabilityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Availability::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Indisponibilité')
            ->setEntityLabelInPlural('Disponibilités')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des disponibilités')
            ->setPageTitle(Crud::PAGE_NEW, 'Bloquer une période')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la période')
            ->setDefaultSort(['startAt' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Bloquez ici vos vacances, congés ou créneaux indisponibles. Les clients ne pourront pas réserver sur ces plages.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('reason')->setLabel('Motif')->setHelp('Ex : Vacances, Congé maladie, Formation')->setRequired(false);
        yield DateTimeField::new('startAt')->setLabel('Début')->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('endAt')->setLabel('Fin')->setFormat('dd/MM/yyyy HH:mm');
        yield BooleanField::new('allDay')->setLabel('Journée(s) entière(s)');
    }
}
