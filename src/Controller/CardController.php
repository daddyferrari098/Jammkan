<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\BoardList;
use App\Form\CardType;
use App\Repository\CardRepository;
use App\Repository\BoardListRepository;
use App\Repository\LabelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/card')]
#[IsGranted('ROLE_USER')]
class CardController extends AbstractController
{
    #[Route('/{id}', name: 'app_card_show', methods: ['GET'])]
    public function show(Card $card): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('view', $card->getBoardList()->getBoard());
        
        return $this->render('card/show.html.twig', [
            'card' => $card,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Card $card, EntityManagerInterface $entityManager, LabelRepository $labelRepository): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $card->getBoardList()->getBoard());
        
        $form = $this->createForm(CardType::class, $card, [
            'board' => $card->getBoardList()->getBoard(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $card->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }

            return $this->redirectToRoute('app_board_show', [
                'id' => $card->getBoardList()->getBoard()->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('card/edit.html.twig', [
            'card' => $card,
            'form' => $form,
            'board' => $card->getBoardList()->getBoard(),
        ]);
    }
    
    #[Route('/{id}/quick-edit', name: 'app_card_quick_edit', methods: ['POST'])]
    public function quickEdit(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $card->getBoardList()->getBoard());
        
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $dueDate = $request->request->get('dueDate');
        
        if (!empty($title)) {
            $card->setTitle($title);
        }
        
        if ($description !== null) {
            $card->setDescription($description);
        }
        
        if ($dueDate !== null) {
            if (empty($dueDate)) {
                $card->setDueDate(null);
            } else {
                $card->setDueDate(new \DateTimeImmutable($dueDate));
            }
        }
        
        $card->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();
        
        if ($request->isXmlHttpRequest()) {
            return $this->json(['status' => 'success']);
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $card->getBoardList()->getBoard()->getId()]);
    }
    
    #[Route('/{id}/move', name: 'app_card_move', methods: ['POST'])]
    public function move(Request $request, Card $card, EntityManagerInterface $entityManager, BoardListRepository $boardListRepository, CardRepository $cardRepository): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $card->getBoardList()->getBoard());
        
        $listId = $request->request->get('listId');
        $position = $request->request->get('position');
        
        if ($listId !== null && $position !== null) {
            $targetList = $boardListRepository->find($listId);
            
            if ($targetList && $targetList->getBoard() === $card->getBoardList()->getBoard()) {
                // Mettre à jour la position des autres cartes dans la liste d'origine
                if ($targetList !== $card->getBoardList()) {
                    $cardsToUpdate = $cardRepository->findBy(['boardList' => $card->getBoardList(), 'position' => ['>', $card->getPosition()]]);
                    foreach ($cardsToUpdate as $cardToUpdate) {
                        $cardToUpdate->setPosition($cardToUpdate->getPosition() - 1);
                    }
                }
                
                // Mettre à jour la position des autres cartes dans la liste cible
                $cardsToUpdate = $cardRepository->findBy(['boardList' => $targetList, 'position' => ['>=', $position]]);
                foreach ($cardsToUpdate as $cardToUpdate) {
                    $cardToUpdate->setPosition($cardToUpdate->getPosition() + 1);
                }
                
                // Déplacer la carte
                $card->setBoardList($targetList);
                $card->setPosition($position);
                $card->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['status' => 'success']);
                }
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $card->getBoardList()->getBoard()->getId()]);
    }
    
    #[Route('/{id}/toggle-completed', name: 'app_card_toggle_completed', methods: ['POST'])]
    public function toggleCompleted(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $card->getBoardList()->getBoard());
        
        $card->setCompleted(!$card->isCompleted());
        $card->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();
        
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'status' => 'success',
                'completed' => $card->isCompleted()
            ]);
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $card->getBoardList()->getBoard()->getId()]);
    }
    
    #[Route('/{id}', name: 'app_card_delete', methods: ['POST'])]
    public function delete(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $card->getBoardList()->getBoard());
        
        if ($this->isCsrfTokenValid('delete'.$card->getId(), $request->request->get('_token'))) {
            $boardId = $card->getBoardList()->getBoard()->getId();
            $entityManager->remove($card);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $card->getBoardList()->getBoard()->getId()]);
    }
}
