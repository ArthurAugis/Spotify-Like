<?php

namespace App\Controller;

use App\Service\RecommendationService;
use App\Repository\RecommendationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recommendations')]
class RecommendationController extends AbstractController
{
    public function __construct(
        private RecommendationService $recommendationService,
        private RecommendationRepository $recommendationRepository
    ) {
    }

    #[Route('/', name: 'app_recommendations')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $recommendations = $this->recommendationService->getFormattedRecommendations($user, 20);
        
        $groupedRecommendations = [];
        foreach ($recommendations as $recommendation) {
            $reason = $recommendation['reason'];
            if (!isset($groupedRecommendations[$reason])) {
                $groupedRecommendations[$reason] = [];
            }
            $groupedRecommendations[$reason][] = $recommendation;
        }

        $this->recommendationRepository->markAsViewedForUser($user);

        return $this->render('recommendations/index.html.twig', [
            'groupedRecommendations' => $groupedRecommendations,
            'totalRecommendations' => count($recommendations)
        ]);
    }

    #[Route('/generate', name: 'app_recommendations_generate', methods: ['POST'])]
    public function generate(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $recommendations = $this->recommendationService->generateRecommendationsForUser($user, 15);
            
            $this->recommendationService->saveRecommendations($recommendations);
            
            return $this->json([
                'success' => true,
                'count' => count($recommendations),
                'message' => 'New recommendations generated successfully!'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api', name: 'app_recommendations_api', methods: ['GET'])]
    public function getRecommendations(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $limit = $request->query->getInt('limit', 10);
        $reason = $request->query->get('reason');

        if ($reason) {
            $recommendations = $this->recommendationRepository->findByUserAndReason($user, $reason, $limit);
        } else {
            $recommendations = $this->recommendationRepository->findActiveRecommendationsForUser($user, $limit);
        }

        $formattedRecommendations = $this->recommendationService->getFormattedRecommendations($user, $limit);

        return $this->json([
            'recommendations' => $formattedRecommendations,
            'count' => count($formattedRecommendations)
        ]);
    }

    #[Route('/{id}/like', name: 'app_recommendations_like', methods: ['POST'])]
    public function like(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $success = $this->recommendationService->markAsLiked($id, $user);

        return $this->json([
            'success' => $success,
            'message' => $success ? 'Recommendation liked!' : 'Recommendation not found'
        ]);
    }

    #[Route('/{id}/dismiss', name: 'app_recommendations_dismiss', methods: ['POST'])]
    public function dismiss(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $success = $this->recommendationService->markAsDismissed($id, $user);

        return $this->json([
            'success' => $success,
            'message' => $success ? 'Recommendation dismissed!' : 'Recommendation not found'
        ]);
    }

    #[Route('/count', name: 'app_recommendations_count', methods: ['GET'])]
    public function getCount(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['count' => 0]);
        }

        $count = $this->recommendationRepository->countUnviewedForUser($user);

        return $this->json(['count' => $count]);
    }
}