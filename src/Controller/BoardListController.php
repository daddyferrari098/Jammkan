<?php

namespace App\Controller;

use App\Entity\BoardList;
use App\Entity\Card;
use App\Repository\BoardListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/boardlist')]
#[IsGranted('ROLE_USER')]
class BoardListController extends AbstractController
{
    #[Route('/{id}/edit-name', name: 'app_boardlist_edit_name', methods: ['POST'])]
    public function editName(Request $request, BoardList $boardList, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $boardList->getBoard());
        
        $name = $request->request->get('name');
        
        if (!empty($name)) {
            $boardList->setName($name);
            $boardList->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $boardList->getBoard()->getId()]);
    }
    
    #[Route('/{id}/update-positions', name: 'app_boardlist_update_positions', methods: ['POST'])]
    public function updatePositions(Request $request, BoardList $boardList, BoardListRepository $boardListRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $boardList->getBoard());
        
        $positions = $request->request->all('positions');
        
        if (!empty($positions)) {
            foreach ($positions as $id => $position) {
                $list = $boardListRepository->find($id);
                if ($list && $list->getBoard() === $boardList->getBoard()) {
                    $list->setPosition($position);
                }
            }
            
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $boardList->getBoard()->getId()]);
    }
    
    #[Route('/{id}', name: 'app_boardlist_delete', methods: ['POST'])]
    public function delete(Request $request, BoardList $boardList, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $boardList->getBoard());
        
        if ($this->isCsrfTokenValid('delete'.$boardList->getId(), $request->request->get('_token'))) {
            $boardId = $boardList->getBoard()->getId();
            $entityManager->remove($boardList);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $boardList->getBoard()->getId()]);
    }
    
    #[Route('/{id}/add-card', name: 'app_boardlist_add_card', methods: ['POST'])]
    public function addCard(Request $request, BoardList $boardList, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $boardList->getBoard());
        
        $title = $request->request->get('title');
        
        if (!empty($title)) {
            // Calculer la position de la nouvelle carte (à la fin)
            $position = count($boardList->getCards()) + 1;
            
            $card = new Card();
            $card->setTitle($title);
            $card->setPosition($position);
            $card->setBoardList($boardList);
            
            $entityManager->persist($card);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'id' => $card->getId(),
                    'uuid' => $card->getUuid()->toRfc4122(),
                    'title' => $card->getTitle(),
                    'position' => $card->getPosition(),
                ]);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $boardList->getBoard()->getId()]);
    }
}
