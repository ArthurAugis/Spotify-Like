<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Track;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit tests for SearchController API routes
 */
class SearchControllerApiTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        
        // Create test user and tracks
        $this->user = $this->createTestUser();
        $this->createTestTracks();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Cleanup
        if ($this->user) {
            $this->entityManager->remove($this->user);
            $this->entityManager->flush();
        }
        
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * Test AJAX search endpoint with valid query
     */
    public function testAjaxSearchWithValidQuery(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/search/ajax?q=test');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('results', $responseData);
        $this->assertIsArray($responseData['results']);
    }

    /**
     * Test AJAX search endpoint with short query
     */
    public function testAjaxSearchWithShortQuery(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/search/ajax?q=a');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('results', $responseData);
        $this->assertEmpty($responseData['results']);
    }

    /**
     * Test AJAX search endpoint without query parameter
     */
    public function testAjaxSearchWithoutQuery(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/search/ajax');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('results', $responseData);
        $this->assertEmpty($responseData['results']);
    }

    /**
     * Test search with specific query that should return results
     */
    public function testAjaxSearchWithSpecificQuery(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/search/ajax?q=Rock');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('results', $responseData);
        $this->assertIsArray($responseData['results']);
        
        // Verify results structure is correct
        if (!empty($responseData['results'])) {
            $results = $responseData['results'];
            
            // Results can contain tracks, playlists, artists
            if (isset($results['tracks'])) {
                $this->assertIsArray($results['tracks']);
            }
            if (isset($results['playlists'])) {
                $this->assertIsArray($results['playlists']);
            }
            if (isset($results['artists'])) {
                $this->assertIsArray($results['artists']);
            }
        }
    }

    /**
     * Test search with special characters
     */
    public function testAjaxSearchWithSpecialCharacters(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/search/ajax?q=' . urlencode('test & special'));
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('results', $responseData);
        $this->assertIsArray($responseData['results']);
    }

    /**
     * Test results limitation for autocompletion
     */
    public function testAjaxSearchResultsLimitation(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('GET', '/search/ajax?q=test');
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('results', $responseData);
        
        // Verify that results count is limited (usually 5 for autocomplete)
        if (isset($responseData['results']['tracks'])) {
            $this->assertLessThanOrEqual(5, count($responseData['results']['tracks']));
        }
    }

    /**
     * Create test user
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test.search@example.com');
        $user->setPassword('$2y$13$password_hash');
        $user->setFirstName('Search');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Create test tracks for search
     */
    private function createTestTracks(): void
    {
        $tracks = [
            ['title' => 'Test Rock Song', 'artist' => 'Rock Artist', 'genre' => 'Rock'],
            ['title' => 'Pop Test Track', 'artist' => 'Pop Artist', 'genre' => 'Pop'],
            ['title' => 'Jazz Test Music', 'artist' => 'Jazz Musician', 'genre' => 'Jazz'],
        ];

        foreach ($tracks as $trackData) {
            $track = new Track();
            $track->setTitle($trackData['title']);
            $track->setArtist($trackData['artist']);
            $track->setGenre($trackData['genre']);
            $track->setUploadedBy($this->user);
            $track->setCreatedAt(new \DateTime());
            $track->setUpdatedAt(new \DateTime());
            
            $this->entityManager->persist($track);
        }
        
        $this->entityManager->flush();
    }
}