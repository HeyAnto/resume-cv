<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
final class AccountEditController extends AbstractController
{
    #[Route('/{username}/account', name: 'app_account_edit')]
    public function index(): Response
    {
        return $this->render('profile-edit/account-edit.html.twig');
    }
}
