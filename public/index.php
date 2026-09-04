<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/session.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';

startSecureSession();

if (isAuthenticated()) {
	header('Location: dashboard.php');
	exit;
}

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim((string) ($_POST['email'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');

	if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
		$errorMessage = 'Email ou palavra-passe inválidos.';
	} elseif (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
		$errorMessage = 'Não foi possível validar o pedido. Tente novamente.';
	} else {
		try {
			$controller = new AuthController();

			if ($controller->authenticate(getDatabaseConnection(), $email, $password)) {
				header('Location: dashboard.php');
				exit;
			}

			$errorMessage = 'Email ou palavra-passe inválidos.';
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errorMessage = 'Não foi possível concluir o login neste momento.';
		}
	}
}

require dirname(__DIR__) . '/app/views/login.php';
