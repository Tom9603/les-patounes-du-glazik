<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Enum\ServiceType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
class BookingCrudController extends AbstractCrudController
{
    public function persistEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $this->autoFillEndTime($entityInstance);
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $this->autoFillEndTime($entityInstance);
        parent::updateEntity($em, $entityInstance);
    }

    private function autoFillEndTime(Booking $booking): void
    {
        if ($booking->getScheduledAt() && !$booking->getScheduledEndAt()) {
            $booking->setScheduledEndAt(
                (clone $booking->getScheduledAt())
                    ->modify('+' . $booking->getServiceType()->durationMinutes() . ' minutes')
            );
        }
    }

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

        $cancel = Action::new('cancel', 'Annuler', 'fa fa-ban')
            ->linkToRoute('app_booking_admin_cancel_form', fn(Booking $b) => ['id' => $b->getId()])
            ->displayIf(fn(Booking $b) => in_array($b->getStatus(), [BookingStatus::Pending, BookingStatus::Confirmed], true))
            ->addCssClass('btn btn-outline-danger btn-sm');

        return $actions
            ->add(Crud::PAGE_INDEX, $confirm)
            ->add(Crud::PAGE_INDEX, $refuse)
            ->add(Crud::PAGE_INDEX, $complete)
            ->add(Crud::PAGE_INDEX, $invoice)
            ->add(Crud::PAGE_INDEX, $cancel)
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('client')->setLabel('Client');
        yield TextField::new('clientPhone')->setLabel('Téléphone')->hideOnForm()->setRequired(false);
        yield TextField::new('clientEmail')->setLabel('Email client')->hideOnForm()->setRequired(false)->onlyOnDetail();
        yield AssociationField::new('animal')->setLabel('Animal')->setRequired(false);
        yield TextField::new('serviceTypeLabel')->setLabel('Service')->hideOnForm();
        yield ChoiceField::new('serviceType')
            ->setLabel('Service')
            ->setChoices(array_combine(
                array_map(fn(ServiceType $c) => $c->label(), ServiceType::cases()),
                ServiceType::cases()
            ))
            ->onlyOnForms();
        yield DateField::new('preferredDate')->setLabel('Date souhaitée');
        yield TextField::new('preferredTime')->setLabel('Heure souhaitée')->setRequired(false);
        yield DateTimeField::new('scheduledAt')->setLabel('RDV fixé')->setRequired(false);
        yield DateTimeField::new('scheduledEndAt')->setLabel('Fin RDV')->setRequired(false);
        yield TextField::new('address')->setLabel('Adresse')->setRequired(false);
        yield NumberField::new('price')->setLabel('Prix (€)')->setNumDecimals(2)->setRequired(false);
        yield TextField::new('statusLabel')
            ->setLabel('Statut')
            ->formatValue(function ($value) {
                $colors = [
                    'En attente' => '#d97706',
                    'Confirmé'   => '#16a34a',
                    'Refusé'     => '#dc2626',
                    'Terminé'    => '#0891b2',
                    'Annulé'     => '#6b7280',
                ];
                $bg = $colors[$value] ?? '#6b7280';
                return \sprintf(
                    '<span style="background:%s;color:#fff;padding:2px 9px;border-radius:12px;font-size:.78em;font-weight:600;">%s</span>',
                    $bg, htmlspecialchars((string) $value)
                );
            })
            ->renderAsHtml()
            ->hideOnForm();
        yield ChoiceField::new('status')
            ->setLabel('Statut')
            ->setChoices(array_combine(
                array_map(fn(BookingStatus $c) => $c->label(), BookingStatus::cases()),
                BookingStatus::cases()
            ))
            ->onlyOnForms();
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
