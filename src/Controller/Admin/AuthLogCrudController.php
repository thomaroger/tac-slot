<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AuthLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AuthLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuthLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Journal d’authentification')
            ->setEntityLabelInPlural('Journaux d’authentification')
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('adherent')->setRequired(false),
            TextField::new('event', 'Événement'),
            DateTimeField::new('createdAt', 'Date'),
            TextField::new('ip', 'IP')->hideOnForm(),
            TextField::new('userAgent', 'User agent')->hideOnForm(),
        ];
    }
}
