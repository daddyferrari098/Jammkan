<?php

namespace App\Controller;

use App\Entity\Label;
use App\Entity\Board;
use App\Entity\Card;
use App\Repository\CardRepository;
use App\Form\LabelType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/label')]
#[IsGranted('ROLE_USER')]
class LabelController extends AbstractController
{
    #[Route('/board/{id}/new', name: 'app_label_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Board $board, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $board);
        
        $label = new Label();
        $label->setBoard($board);
        
        $form = $this->createForm(LabelType::class, $label);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($label);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'id' => $label->getId(),
                    'name' => $label->getName(),
                    'color' => $label->getColor()
                ]);
            }

            return $this->redirectToRoute('app_board_show', ['id' => $board->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('label/new.html.twig', [
            'label' => $label,
            'form' => $form,
            'board' => $board,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_label_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Label $label, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $label->getBoard());
        
        $form = $this->createForm(LabelType::class, $label);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $label->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }

            return $this->redirectToRoute('app_board_show', ['id' => $label->getBoard()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('label/edit.html.twig', [
            'label' => $label,
            'form' => $form,
            'board' => $label->getBoard(),
        ]);
    }
    
    #[Route('/{id}/toggle-on-card/{cardId}', name: 'app_label_toggle_on_card', methods: ['POST'])]
    public function toggleOnCard(Request $request, Label $label, int $cardId, CardRepository $cardRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $label->getBoard());
        
        $card = $cardRepository->find($cardId);
        
        if ($card && $card->getBoardList()->getBoard() === $label->getBoard()) {
            if ($card->getLabels()->contains($label)) {
                $card->removeLabel($label);
            } else {
                $card->addLabel($label);
            }
            
            $card->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'hasLabel' => $card->getLabels()->contains($label)
                ]);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $label->getBoard()->getId()]);
    }
    
    #[Route('/{id}', name: 'app_label_delete', methods: ['POST'])]
    public function delete(Request $request, Label $label, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur courant est le propriétaire du tableau
        $this->denyAccessUnlessGranted('edit', $label->getBoard());
        
        if ($this->isCsrfTokenValid('delete'.$label->getId(), $request->request->get('_token'))) {
            $boardId = $label->getBoard()->getId();
            $entityManager->remove($label);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success']);
            }
        }
        
        return $this->redirectToRoute('app_board_show', ['id' => $label->getBoard()->getId()]);
    }
}
