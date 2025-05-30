<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_ADMIN')] // Require ADMIN for all category actions
class CategoryController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = [
        'name' => 'name',
        'assigned_hosts' => 'hostsCount',
        'assigned_users' => 'usersCount',
        'createdAt' => 'createdAt',
        'updatedAt' => 'updatedAt',
    ];

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $sortByInput = $request->query->get('sort_by', 'name');
        $sortDirectionInput = $request->query->get('sort_direction', 'ASC');

        $sortBy = self::ALLOWED_SORT_FIELDS[$sortByInput] ?? self::ALLOWED_SORT_FIELDS['name'];
        $sortDirection = strtoupper($sortDirectionInput) === 'DESC' ? 'DESC' : 'ASC';

        $categories = $this->categoryRepository->findWithSorting($sortBy, $sortDirection);

        return $this->render('pages/category/index.html.twig', [
            'categories' => $categories,
            'current_sort_by' => $sortByInput,
            'current_sort_direction' => $sortDirection,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'category.flash.created_successfully');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'category.flash.updated_successfully');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete' . $category->getId(), $submittedToken)) {
            $this->addFlash('error', 'common.invalid_csrf_token');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        // Optional: Check if category is in use before deleting?
        // if (!$category->getHosts()->isEmpty() || !$category->getUsers()->isEmpty()) {
        //     $this->addFlash('warning', 'category.cannot_delete_in_use');
        //     return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        // }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->addFlash('success', 'category.flash.deleted_successfully');

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
