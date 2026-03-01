<?php

namespace App\Controller\Admin;

use App\Entity\Slot;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class SlotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Slot::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Créneau')
            ->setEntityLabelInPlural('Créneaux')
            ->setDefaultSort(['startAt' => 'ASC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('startAt', 'Début')
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('endAt', 'Fin')
            ->setFormat('dd/MM/yyyy HH:mm');

        yield IntegerField::new('maxPlaces', 'Capacité max');

        yield IntegerField::new('reservedPlaces', 'Places réservées')
            ->setHelp('Se met à jour automatiquement via réservation');

        yield BooleanField::new('isClosed', 'Fermé ?');
        yield BooleanField::new('requiresAirKey', 'Requiert Air Key');

    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('startAt')
            ->add('isClosed')
            ->add('requiresAirKey');
    }
}