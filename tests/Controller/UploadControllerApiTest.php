<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit tests for UploadController API routes
 */
class UploadControllerApiTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;
    private $uploadsDirectory;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->uploadsDirectory = $container->getParameter('kernel.project_dir') . '/public/uploads';
        
        // Create test user
        $this->user = $this->createTestUser();
        
        // Create test uploads folder if it doesn't exist
        if (!is_dir($this->uploadsDirectory . '/profiles')) {
            mkdir($this->uploadsDirectory . '/profiles', 0777, true);
        }
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
     * Test avatar upload with valid file
     */
    public function testUploadProfilePictureWithValidFile(): void
    {
        $this->client->loginUser($this->user);
        
        // Create test image file
        $testImagePath = $this->createTestImage();
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test-avatar.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('avatar_url', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertStringContainsString('/uploads/profiles/', $responseData['avatar_url']);
        
        // Clean up test file
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    /**
     * Test avatar upload without authentication
     */
    public function testUploadProfilePictureUnauthenticated(): void
    {
        $testImagePath = $this->createTestImage();
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test-avatar.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
        
        // Cleanup
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    /**
     * Test avatar upload without file
     */
    public function testUploadProfilePictureWithoutFile(): void
    {
        $this->client->loginUser($this->user);
        
        $this->client->request('POST', '/upload/profile-picture');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('No file selected', $responseData['error']);
    }

    /**
     * Test avatar upload with unsupported format
     */
    public function testUploadProfilePictureWithUnsupportedFormat(): void
    {
        $this->client->loginUser($this->user);
        
        // Create text file instead of image
        $testFilePath = sys_get_temp_dir() . '/test_file.txt';
        file_put_contents($testFilePath, 'This is not an image');
        
        $uploadedFile = new UploadedFile(
            $testFilePath,
            'test-file.txt',
            'text/plain',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Format not supported', $responseData['error']);
        
        // Cleanup
        if (file_exists($testFilePath)) {
            unlink($testFilePath);
        }
    }

    /**
     * Test avatar upload with PNG image (supported format)
     */
    public function testUploadProfilePictureWithPngFormat(): void
    {
        $this->client->loginUser($this->user);
        
        $testImagePath = $this->createTestImage('png');
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test-avatar.png',
            'image/png',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        
        // Cleanup
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    /**
     * Test avatar upload with GIF image (supported format)
     */
    public function testUploadProfilePictureWithGifFormat(): void
    {
        $this->client->loginUser($this->user);
        
        $testImagePath = $this->createTestImage('gif');
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test-avatar.gif',
            'image/gif',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        
        // Nettoyer
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    /**
     * Test existing avatar replacement
     */
    public function testReplaceExistingProfilePicture(): void
    {
        $this->client->loginUser($this->user);
        
        // Set existing avatar for user
        $this->user->setProfilePicture('existing_avatar.jpg');
        $this->entityManager->flush();
        
        $testImagePath = $this->createTestImage();
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'new-avatar.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertStringContainsString('/uploads/profiles/', $responseData['avatar_url']);
        
        // Verify user has new avatar filename
        $this->entityManager->refresh($this->user);
        $this->assertNotEquals('existing_avatar.jpg', $this->user->getProfilePicture());
        
        // Cleanup
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    /**
     * Test JSON response structure validation
     */
    public function testProfilePictureResponseStructure(): void
    {
        $this->client->loginUser($this->user);
        
        $testImagePath = $this->createTestImage();
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'structure-test.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->client->request('POST', '/upload/profile-picture', [], [
            'profile_picture' => $uploadedFile
        ]);

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify complete response structure
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('avatar_url', $responseData);
        $this->assertIsBool($responseData['success']);
        $this->assertIsString($responseData['avatar_url']);
        $this->assertTrue($responseData['success']);
        
        // Verify URL format
        $this->assertStringStartsWith('/uploads/profiles/', $responseData['avatar_url']);
        $this->assertStringEndsWith('.jpg', $responseData['avatar_url']);
        
        // Cleanup
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    /**
     * Create test user
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test.upload@example.com');
        $user->setPassword('$2y$13$password_hash');
        $user->setFirstName('Upload');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Create test image
     */
    private function createTestImage(string $format = 'jpeg'): string
    {
        $width = 100;
        $height = 100;
        
        $image = imagecreatetruecolor($width, $height);
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $backgroundColor);
        
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 10, 40, 'TEST', $textColor);
        
        $tempFile = sys_get_temp_dir() . '/test_image_' . uniqid() . '.' . $format;
        
        switch ($format) {
            case 'png':
                imagepng($image, $tempFile);
                break;
            case 'gif':
                imagegif($image, $tempFile);
                break;
            default:
                imagejpeg($image, $tempFile);
                break;
        }
        
        imagedestroy($image);
        
        return $tempFile;
    }
}