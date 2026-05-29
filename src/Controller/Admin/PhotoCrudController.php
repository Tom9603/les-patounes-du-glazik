<?php

namespace App\Controller\Admin;

use App\Entity\Photo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class PhotoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Photo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo')
            ->setEntityLabelInPlural('Photos')
            ->setDefaultSort(['position' => 'ASC', 'createdAt' => 'DESC'])
            ->setPageTitle(Crud::PAGE_INDEX, 'Galerie photos')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une photo')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la photo')
            ->overrideTemplate('crud/index', 'admin/photo_mosaic.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield Field::new('imageFile')
            ->setFormType(VichImageType::class)
            ->setLabel('Image')
            ->onlyOnForms();

        yield ImageField::new('filename')
            ->setLabel('Aperçu')
            ->setBasePath('/uploads/photos/')
            ->onlyOnIndex()
            ->setSortable(false);

        yield TextField::new('alt')
            ->setLabel('Texte alternatif')
            ->setHelp('Description de l\'image pour l\'accessibilité');

        yield TextField::new('caption')
            ->setLabel('Légende')
            ->setHelp('Texte affiché sous la photo dans la galerie')
            ->hideOnIndex();

        yield IntegerField::new('position')
            ->setLabel('Ordre d\'affichage')
            ->setHelp('Plus le chiffre est petit, plus la photo apparaît en premier');
    }
}
