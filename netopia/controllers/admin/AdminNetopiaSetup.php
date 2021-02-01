<?php
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminNetopiaSetupController extends AdminNetopiaConfigurationController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function index() {
        die('INDEX');
    }

    public function test() {
        die('TEST TEST ');
    }

}
