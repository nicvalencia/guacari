<?php
declare(strict_types=1);

namespace App\Controladores;

use Slim\Flash\Messages;
use Twig\Extension\RuntimeExtensionInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * TwigMessagesRuntime class.
 */
class TwigMessagesRuntime extends \Twig\Extension\AbstractExtension 
{
    /**
     * @var Messages
     */
    protected $flash;

    /**
     * @param Messages $flash The flash
     */
    public function __construct()
    {   
        $this->flash = new Messages;
    }



    public function getFunctions(){
        return [
            new \Twig\TwigFunction('error', array($this,'getMessages')),
        ];        
    }

    /**
     * @param string $key The key
     *
     * @return bool
     */
    public function hasMessage(string $key): bool
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            file_put_contents(LOGS_DIR . 'flash.log', 'flash not active' . \PHP_EOL, \FILE_APPEND);

            return false;
        }

        return $this->flash->hasMessage($key);
    }

    /**
     * @param null|string $key The key
     *
     * @return array|mixed
     */
    public function getMessages(?string $key = null): mixed
    {
        
        if ($key !== null) {
            return $this->flash->getMessage($key);
        }

        return $this->flash->getMessages();
    }

    /**
     * @param string $key The key
     *
     * @return string
     */
    public function formData(string $key): string
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            file_put_contents(LOGS_DIR . 'flash.log', 'flash not active' . \PHP_EOL, \FILE_APPEND);

            return '';
        }
        $old = $this->flash->getFirstMessage('old');

        return $old[$key] ?? '';
    }

    /**
     * @param string $key The key
     *
     * @return array
     */
    public function errors(string $key): array
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            file_put_contents(LOGS_DIR . 'flash.log', 'flash not active' . \PHP_EOL, \FILE_APPEND);

            return [];
        }
        $errors = $this->flash->getFirstMessage('errors');

        return $errors[$key] ?? [];
    }
}
