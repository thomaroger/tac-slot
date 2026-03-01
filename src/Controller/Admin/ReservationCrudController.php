<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setDefaultSort(['reservedAt' => 'DESC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('slot', 'Créneau'),
            AssociationField::new('user', 'Adhérent'),
            ChoiceField::new('status', 'Statut')
                ->setChoices(array(
                    Reservation::STATUS_CONFIRMED => Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_PENDING => Reservation::STATUS_PENDING,
                    Reservation::STATUS_CANCELLED => Reservation::STATUS_CANCELLED,
                    Reservation::STATUS_NO_SHOW => Reservation::STATUS_NO_SHOW
                ))
                ->renderExpanded(false)
                ->renderAsBadges(),
            BooleanField::new('checkedIn', 'Checked ?'),
            
            DateTimeField::new('reservedAt', 'Réservé le'),
            DateTimeField::new('checkedInAt', 'Checké le'),
            DateTimeField::new('cancelledAt', 'Annulé le'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('slot')
            ->add('user');
    }
}