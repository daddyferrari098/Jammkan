<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Form\BoardType;
use App\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/board')]
#[IsGranted('ROLE_USER')]
class BoardController extends AbstractController
{
    #[Route('/', name: 'app_board_index', methods: ['GET'])]
    public function index(BoardRepository $boardRepository): Response
    {
        return $this->render('board/index.html.twig', [
            'boards' => $boardRepository->findBy(['owner' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_board_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $board = new Board();
        $board->setOwner($this->getUser());
        
        $form = $this->createForm(BoardType::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($board);
            $entityManager->flush();

            return $this->redirectToRoute('app_board_show', ['id' => $board->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('board/new.html.twig', [
            'board' => $board,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_board_show', methods: ['GET'])]
    public function show(Board $board): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('view', $board);
        
        return $this->render('board/show.html.twig', [
            'board' => $board,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_board_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Board $board, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $board);
        
        $form = $this->createForm(BoardType::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $board->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('app_board_show', ['id' => $board->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('board/edit.html.twig', [
            'board' => $board,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_board_delete', methods: ['POST'])]
    public function delete(Request $request, Board $board, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('delete', $board);
        
        if ($this->isCsrfTokenValid('delete'.$board->getId(), $request->request->get('_token'))) {
            $entityManager->remove($board);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_board_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/{id}/add-list', name: 'app_board_add_list', methods: ['POST'])]
    public function addList(Request $request, Board $board, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $board);
        
        $name = $request->request->get('name');
        
        if (!empty($name)) {
            // Calculer la position de la nouvelle liste (à la fin)
            $position = count($board->getLists()) + 1;
            
            $list = new BoardList();
            $list->setName($name);
            $list->setPosition($position);
            $list->setBoard($board);
            
            $entityManager->persist($list);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'id' => $list->getId(),
                    'uuid' => $list->getUuid()->toRfc4122(),
                    'name' => $list->getName(),
                    'position' => $list->getPosition(),
                ]);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $board->getId()]);
    }
}
