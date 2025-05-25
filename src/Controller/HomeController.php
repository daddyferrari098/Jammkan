<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\BoardRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(BoardRepository $boardRepository): Response
    {
        // Si l'utilisateur est connecté, on affiche ses tableaux
        if ($this->getUser()) {
            $boards = $boardRepository->findBy(['owner' => $this->getUser()], ['createdAt' => 'DESC']);
            
            return $this->render('home/index.html.twig', [
                'boards' => $boards,
            ]);
        }
        
        // Sinon on affiche la page d'accueil publique
        return $this->render('home/landing.html.twig');
    }
    
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(BoardRepository $boardRepository): Response
    {
        $boards = $boardRepository->findBy(['owner' => $this->getUser()], ['createdAt' => 'DESC']);
        
        return $this->render('home/dashboard.html.twig', [
            'boards' => $boards,
        ]);
    }
}
