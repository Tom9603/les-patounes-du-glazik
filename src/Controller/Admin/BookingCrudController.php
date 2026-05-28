<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Enum\ServiceType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
class BookingCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des réservations')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->overrideTemplate('crud/index', 'admin/booking_index.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $confirm = Action::new('confirm', 'Confirmer', 'fa fa-check')
            ->linkToRoute('app_booking_admin_confirm_form', fn(Booking $b) => ['id' => $b->getId()])
            ->displayIf(fn(Booking $b) => $b->getStatus() === BookingStatus::Pending)
            ->addCssClass('btn btn-success btn-sm');

        $refuse = Action::new('refuse', 'Refuser', 'fa fa-times')
            ->linkToRoute('app_booking_admin_refuse_form', fn(Booking $b) => ['id' => $b->getId()])
            ->displayIf(fn(Booking $b) => $b->getStatus() === BookingStatus::Pending)
            ->addCssClass('btn btn-danger btn-sm');

        $complete = Action::new('complete', 'Terminer', 'fa fa-flag-checkered')
            ->linkToRoute('app_booking_admin_complete_form', fn(Booking $b) => ['id' => $b->getId()])
            ->displayIf(fn(Booking $b) => $b->getStatus() === BookingStatus::Confirmed)
            ->addCssClass('btn btn-secondary btn-sm');

        $invoice = Action::new('invoice', 'Facture', 'fa fa-file-invoice')
            ->linkToRoute('app_admin_invoice_create_form', fn(Booking $b) => ['bookingId' => $b->getId()])
            ->displayIf(fn(Booking $b) => in_array($b->getStatus(), [BookingStatus::Confirmed, BookingStatus::Completed]) && $b->getInvoices()->isEmpty())
            ->addCssClass('btn btn-outline-primary btn-sm');

        return $actions
            ->add(Crud::PAGE_INDEX, $confirm)
            ->add(Crud::PAGE_INDEX, $refuse)
            ->add(Crud::PAGE_INDEX, $complete)
            ->add(Crud::PAGE_INDEX, $invoice)
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('client')->setLabel('Client');
        yield AssociationField::new('animal')->setLabel('Animal')->setRequired(false);
        yield ChoiceField::new('serviceType')
            ->setLabel('Service')
            ->setChoices(ServiceType::choices());
        yield DateField::new('preferredDate')->setLabel('Date souhaitée');
        yield TextField::new('preferredTime')->setLabel('Heure souhaitée')->setRequired(false);
        yield DateTimeField::new('scheduledAt')->setLabel('RDV fixé')->setRequired(false);
        yield DateTimeField::new('scheduledEndAt')->setLabel('Fin RDV')->setRequired(false);
        yield TextField::new('address')->setLabel('Adresse')->setRequired(false);
        yield NumberField::new('price')->setLabel('Prix (€)')->setNumDecimals(2)->setRequired(false);
        yield ChoiceField::new('status')
            ->setLabel('Statut')
            ->setChoices(BookingStatus::choices())
            ->renderAsBadges([
                BookingStatus::Pending->value => 'warning',
                BookingStatus::Confirmed->value => 'success',
                BookingStatus::Refused->value => 'danger',
                BookingStatus::Completed->value => 'info',
                BookingStatus::Cancelled->value => 'secondary',
            ]);
        yield TextareaField::new('clientNotes')->setLabel('Notes client')->setRequired(false)->onlyOnDetail();
        yield TextareaField::new('adminNotes')->setLabel('Notes admin')->setRequired(false)->onlyOnForms();
        yield DateTimeField::new('createdAt')->setLabel('Créée le')->onlyOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setLabel('Statut')->setChoices(BookingStatus::choices()))
            ->add(ChoiceFilter::new('serviceType')->setLabel('Service')->setChoices(ServiceType::choices()));
    }
}
