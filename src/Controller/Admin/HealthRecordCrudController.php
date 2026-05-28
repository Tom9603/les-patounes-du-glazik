<?php

namespace App\Controller\Admin;

use App\Entity\HealthRecord;
use App\Enum\HealthRecordType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HealthRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HealthRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fiche santé')
            ->setEntityLabelInPlural('Fiches santé')
            ->setPageTitle(Crud::PAGE_INDEX, 'Suivi santé')
            ->setDefaultSort(['recordedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $types = [];
        foreach (HealthRecordType::cases() as $case) {
            $types[$case->label()] = $case->value;
        }

        yield AssociationField::new('animal')->setLabel('Animal');
        yield ChoiceField::new('type')
            ->setLabel('Type')
            ->setChoices($types);
        yield TextField::new('title')->setLabel('Intitulé');
        yield DateField::new('recordedAt')->setLabel('Date')->setRequired(false);
        yield DateField::new('nextDueAt')->setLabel('Prochain rappel')->setRequired(false);
        yield TextareaField::new('notes')->setLabel('Notes')->setRequired(false)->onlyOnForms();
    }
}
