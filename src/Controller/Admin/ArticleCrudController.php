<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title')->setLabel('Titre');
        yield TextField::new('slug')->setLabel('Slug (URL)')->onlyOnIndex();
        yield AssociationField::new('category')->setLabel('Catégorie')->setRequired(false);
        yield ImageField::new('featuredImageFilename')
            ->setLabel('Image à la une')
            ->setUploadDir('public/uploads/articles/')
            ->setBasePath('/uploads/articles/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
        yield TextareaField::new('excerpt')
            ->setLabel('Résumé')
            ->setRequired(false)
            ->setHelp('Court résumé affiché dans la liste des articles');
        yield TextEditorField::new('content')
            ->setLabel('Contenu')
            ->onlyOnForms()
            ->setNumOfRows(25);
        yield BooleanField::new('isPublished')->setLabel('Publié');
        yield DateTimeField::new('publishedAt')->setLabel('Publié le')->onlyOnIndex();
        yield Field::new('viewCount')->setLabel('Vues')->onlyOnIndex()->setFormTypeOption('mapped', false);
        yield DateTimeField::new('createdAt')->setLabel('Créé le')->onlyOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
