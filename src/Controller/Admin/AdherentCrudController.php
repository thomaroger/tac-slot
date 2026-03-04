<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Adherent;
use App\Repository\AdherentRepository;
use App\Service\AdherentImportService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdherentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AdherentRepository $adherentRepository,
        private readonly AdherentImportService $adherentImportService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Adherent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Adhérent')
            ->setEntityLabelInPlural('Adhérents')
            ->setDefaultSort([
                'lastName' => 'ASC',
                'firstName' => 'ASC',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $levels = [
            'National' => 'National',
            'Régional' => 'Régional',
            'Départemental' => 'Départemental',
            'Débutant/Loisirs' => 'Débutant/Loisirs',
            'Droit de Paille' => 'Droit de Paille',
        ];

        $roles = [
            'Utilisateur' => 'utilisateur',
            'Administrateur' => 'administrateur',
        ];

        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName', 'Prénom'),
            TextField::new('lastName', 'Nom'),
            TextField::new('licenseNumber', 'Numéro de licence'),
            EmailField::new('email', 'Email'),
            ChoiceField::new('level', 'Niveau')
                ->setChoices($levels)
                ->renderExpanded(false)
                ->renderAsBadges(),
            BooleanField::new('canOpenShoot', 'Tir libre'),
            BooleanField::new('emailVerified', 'Actif'),
            BooleanField::new('airKey', 'Air key'),
            ChoiceField::new('role', 'Rôle')
                ->setChoices($roles)
                ->renderAsBadges(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
            DateTimeField::new('deletedAt', 'Supprimé le')->hideOnForm(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $softDelete = Action::new('softDelete', 'Supprimer')
            ->linkToCrudAction('softDelete')
            ->addCssClass('text-danger')
            ->displayAsButton();

        $import = Action::new('import', 'Importer CSV')
            ->linkToRoute('admin_adherents_import');

        return $actions
            ->add(Crud::PAGE_INDEX, $softDelete)
            ->add(Crud::PAGE_INDEX, $import)
            ->disable(Action::DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('lastName')
            ->add('email')
            ->add('licenseNumber')
            ->add('role')
            ->add('canOpenShoot')
            ->add('emailVerified')
            ->add('airKey');
    }

    public function softDelete(Request $request): RedirectResponse
    {
        $id = $request->query->get('entityId');
        if (! $id) {
            return $this->redirect($request->headers->get('referer') ?? $this->urlGenerator->generate('admin'));
        }

        $adherent = $this->adherentRepository->find($id);
        if ($adherent instanceof Adherent) {
            $adherent->setDeletedAt(new \DateTime());
            $adherent->setUpdatedAt(new DateTimeImmutable());
            $this->em->flush();
        }

        return $this->redirect($request->headers->get('referer') ?? $this->urlGenerator->generate('admin'));
    }

    #[Route('/admin/adherents/import', name: 'admin_adherents_import')]
    public function import(Request $request): Response
    {
        $flashBag = $this->getFlashBag();
        $report = null;

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('import_file');
            if (! $file instanceof UploadedFile) {
                $flashBag?->add('danger', 'Aucun fichier n’a été envoyé.');
            } else {
                $report = $this->adherentImportService->importCsvFile($file);
                $flashBag?->add(
                    'success',
                    sprintf(
                        'Import terminé: %d traité(s) (%d créés, %d mis à jour, %d ignorés).',
                        $report['processed'],
                        $report['created'],
                        $report['updated'],
                        $report['ignored']
                    )
                );
            }
        }

        return $this->render('admin/adherents/import.html.twig', [
            'report' => $report,
        ]);
    }

    #[Route('/admin/adherents/import/example.csv', name: 'admin_adherents_import_example')]
    public function downloadImportExample(): StreamedResponse
    {
        $response = new StreamedResponse(function (): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv(
                $output,
                [
                    'Numero de licence',
                    'Prenom',
                    'Nom',
                    'Email',
                    'Niveau',
                    'Air key',
                    'Tir libre',
                    'Actif',
                    'Role',
                    'supprime',
                ],
                ';'
            );
            fputcsv(
                $output,
                [
                    '1234567A',
                    'Thomas',
                    'Roger',
                    'thomas.roger@example.org',
                    'National',
                    'oui',
                    'oui',
                    'oui',
                    'administrateur',
                    'non',
                ],
                ';'
            );
            fputcsv(
                $output,
                [
                    '7654321B',
                    'Emma',
                    'Martin',
                    'emma.martin@example.org',
                    'Departemental',
                    'non',
                    'oui',
                    'oui',
                    'utilisateur',
                    'non',
                ],
                ';'
            );

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="exemple_import_adherents.csv"');

        return $response;
    }

    private function getFlashBag(): ?FlashBagInterface
    {
        $session = $this->requestStack->getSession();

        return $session?->getFlashBag();
    }
}
