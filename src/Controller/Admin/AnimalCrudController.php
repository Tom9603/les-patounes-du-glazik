<?php

namespace App\Controller\Admin;

use App\Entity\Animal;
use App\Enum\AnimalSex;
use App\Enum\AnimalSpecies;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class AnimalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Animal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Animal')
            ->setEntityLabelInPlural('Animaux')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des animaux')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name')->setLabel('Nom');
        yield ChoiceField::new('species')
            ->setLabel('Espèce')
            ->setChoices(AnimalSpecies::choices())
            ->renderAsBadges(false);
        yield TextField::new('breed')->setLabel('Race')->setRequired(false);
        yield ChoiceField::new('sex')
            ->setLabel('Sexe')
            ->setChoices(AnimalSex::choices())
            ->setRequired(false);
        yield DateField::new('birthDate')->setLabel('Date de naissance')->setRequired(false);
        yield TextField::new('color')->setLabel('Couleur')->setRequired(false);
        yield TextField::new('microchip')->setLabel('Numéro de puce')->setRequired(false);
        yield BooleanField::new('sterilized')->setLabel('Stérilisé(e)')->renderAsSwitch(true);
        yield TextareaField::new('healthNotes')->setLabel('Notes de santé')->setRequired(false)->onlyOnForms();
        yield AssociationField::new('owner')->setLabel('Propriétaire')->onlyOnIndex();
        yield DateTimeField::new('createdAt')->setLabel('Créé le')->onlyOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(ChoiceFilter::new('species')->setLabel('Espèce')->setChoices(AnimalSpecies::choices()));
    }
}
