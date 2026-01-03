<?php
class MymoduleApiModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode([
            'ok' => true,
            'received' => $data
        ]);
        exit;
    }
}