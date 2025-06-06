<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = [
        'name' => 'name',
        'email' => 'email',
        'createdAt' => 'createdAt',
        'updatedAt' => 'updatedAt',
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $sortByInput = $request->query->get('sort_by', 'name');
        $sortDirectionInput = $request->query->get('sort_direction', 'ASC');

        $sortBy = self::ALLOWED_SORT_FIELDS[$sortByInput] ?? self::ALLOWED_SORT_FIELDS['name'];
        $sortDirection = strtoupper($sortDirectionInput) === 'DESC' ? 'DESC' : 'ASC';

        $users = $this->userRepository->findWithSorting($sortBy, $sortDirection);

        return $this->render('pages/user/index.html.twig', [
            'users' => $users,
            'current_sort_by' => $sortByInput,
            'current_sort_direction' => $sortDirection,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['validation_groups' => ['Default', 'registration']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ?string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            if ($plainPassword !== null && $plainPassword !== '') {
                $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'user.flash.created_successfully');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        User $user,
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ?string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            if ($plainPassword !== null && $plainPassword !== '') {
                $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'user.flash.updated_successfully');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        User $user,
    ): Response {
        $submittedToken = $request->request->get('_token');

        // CSRF token check
        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $submittedToken)) {
            $this->addFlash('error', 'common.invalid_csrf_token');

            // Return early if the token is invalid
            return $this->redirectToRoute('app_host_index', [], Response::HTTP_SEE_OTHER);
        }

        // Prevent logged-in user from deleting themselves
        $loggedInUser = $this->getUser();
        if ($loggedInUser instanceof User && $loggedInUser->getId() === $user->getId()) {
            $this->addFlash('error', 'user.flash.cannot_delete_self');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'user.flash.deleted_successfully');

        // Redirect after successful deletion
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
