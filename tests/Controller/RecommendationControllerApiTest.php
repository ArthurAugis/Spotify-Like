<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Track;
use App\Entity\Recommendation;
use App\Repository\UserRepository;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit tests for RecommendationController API routes
 */
class RecommendationControllerApiTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        
        // Create test user
        $this->user = $this->createTestUser();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test database
        if ($this->user) {
            $this->entityManager->remove($this->user);
            $this->entityManager->flush();
        }
        
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * Test GET /recommendations/api endpoint with authenticated user
     */
    public function testGetRecommendationsApiAuthenticated(): void
    {
        // Authenticate user
        $this->client->loginUser($this->user);
        
        // API call
        $this->client->request('GET', '/recommendations/api');
        
        // Assertions
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('recommendations', $responseData);
        $this->assertArrayHasKey('count', $responseData);
        $this->assertIsArray($responseData['recommendations']);
        $this->assertIsInt($responseData['count']);
    }

    /**
     * Test GET /recommendations/api endpoint without authentication
     */
    public function testGetRecommendationsApiUnauthenticated(): void
    {
        $this->client->request('GET', '/recommendations/api');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }

    /**
     * Test GET /recommendations/api endpoint with limit parameter
     */
    public function testGetRecommendationsApiWithLimit(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/recommendations/api?limit=5');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertLessThanOrEqual(5, $responseData['count']);
    }

    /**
     * Test GET /recommendations/api endpoint with reason parameter
     */
    public function testGetRecommendationsApiWithReason(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/recommendations/api?reason=genre');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('recommendations', $responseData);
        $this->assertArrayHasKey('count', $responseData);
    }

    /**
     * Test POST /recommendations/generate endpoint with authenticated user
     */
    public function testGenerateRecommendationsAuthenticated(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('POST', '/recommendations/generate');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('count', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertIsInt($responseData['count']);
        $this->assertStringContainsString('generated successfully', $responseData['message']);
    }

    /**
     * Test POST /recommendations/generate endpoint without authentication
     */
    public function testGenerateRecommendationsUnauthenticated(): void
    {
        $this->client->request('POST', '/recommendations/generate');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }

    /**
     * Test POST /recommendations/{id}/like endpoint with valid recommendation
     */
    public function testLikeRecommendationValid(): void
    {
        $this->client->loginUser($this->user);
        
        // Create test recommendation
        $recommendation = $this->createTestRecommendation();
        
        $this->client->request('POST', '/recommendations/' . $recommendation->getId() . '/like');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('liked', $responseData['message']);
    }

    /**
     * Test POST /recommendations/{id}/like endpoint without authentication
     */
    public function testLikeRecommendationUnauthenticated(): void
    {
        $this->client->request('POST', '/recommendations/1/like');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }

    /**
     * Test POST /recommendations/{id}/dismiss endpoint with valid recommendation
     */
    public function testDismissRecommendationValid(): void
    {
        $this->client->loginUser($this->user);
        
        $recommendation = $this->createTestRecommendation();
        
        $this->client->request('POST', '/recommendations/' . $recommendation->getId() . '/dismiss');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('dismissed', $responseData['message']);
    }

    /**
     * Test POST /recommendations/{id}/dismiss endpoint without authentication
     */
    public function testDismissRecommendationUnauthenticated(): void
    {
        $this->client->request('POST', '/recommendations/1/dismiss');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }

    /**
     * Test GET /recommendations/count endpoint with authenticated user
     */
    public function testGetRecommendationsCountAuthenticated(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/recommendations/count');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('count', $responseData);
        $this->assertIsInt($responseData['count']);
        $this->assertGreaterThanOrEqual(0, $responseData['count']);
    }

    /**
     * Test GET /recommendations/count endpoint without authentication
     */
    public function testGetRecommendationsCountUnauthenticated(): void
    {
        $this->client->request('GET', '/recommendations/count');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('count', $responseData);
        $this->assertEquals(0, $responseData['count']);
    }

    /**
     * Test with non-existent recommendation ID
     */
    public function testLikeNonExistentRecommendation(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('POST', '/recommendations/99999/like');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('not found', $responseData['message']);
    }

    /**
     * Test recommendation data structure returned
     */
    public function testRecommendationDataStructure(): void
    {
        $this->client->loginUser($this->user);
        
        // Create some test tracks and recommendations
        $this->createTestTrackAndRecommendation();
        
        $this->client->request('GET', '/recommendations/api?limit=1');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('recommendations', $responseData);
        $this->assertArrayHasKey('count', $responseData);
        
        if ($responseData['count'] > 0) {
            $recommendation = $responseData['recommendations'][0];
            
            // Verify recommendation structure
            $this->assertArrayHasKey('id', $recommendation);
            $this->assertArrayHasKey('reason', $recommendation);
            $this->assertArrayHasKey('score', $recommendation);
        }
    }

    /**
     * Create test user
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test.recommendation@example.com');
        $user->setPassword('$2y$13$password_hash');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Create test recommendation
     */
    private function createTestRecommendation(): Recommendation
    {
        $track = new Track();
        $track->setTitle('Test Track');
        $track->setArtist('Test Artist');
        $track->setUploadedBy($this->user);
        $track->setCreatedAt(new \DateTime());
        $track->setUpdatedAt(new \DateTime());
        
        $recommendation = new Recommendation();
        $recommendation->setUser($this->user);
        $recommendation->setRecommendedTrack($track);
        $recommendation->setReason('genre');
        $recommendation->setScore(0.85);
        $recommendation->setCreatedAt(new \DateTime());
        $recommendation->setViewed(false);
        
        $this->entityManager->persist($track);
        $this->entityManager->persist($recommendation);
        $this->entityManager->flush();
        
        return $recommendation;
    }

    /**
     * Create track and recommendation for structure tests
     */
    private function createTestTrackAndRecommendation(): void
    {
        $track = new Track();
        $track->setTitle('Structure Test Track');
        $track->setArtist('Structure Test Artist');
        $track->setGenre('Pop');
        $track->setUploadedBy($this->user);
        $track->setCreatedAt(new \DateTime());
        $track->setUpdatedAt(new \DateTime());
        
        $recommendation = new Recommendation();
        $recommendation->setUser($this->user);
        $recommendation->setRecommendedTrack($track);
        $recommendation->setReason('genre');
        $recommendation->setScore(0.90);
        $recommendation->setCreatedAt(new \DateTime());
        $recommendation->setViewed(false);
        
        $this->entityManager->persist($track);
        $this->entityManager->persist($recommendation);
        $this->entityManager->flush();
    }
}