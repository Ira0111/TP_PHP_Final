<?php

class UserController
{
    private ?PDO $pdo = null;

    public function __construct()
    {
        global $pdo;

        if (!($pdo instanceof PDO)) {
            throw new Exception(
                'Connexion BDD non initialisée. ' .
                    'Vérifie que config.php fait bien "require_once ROOT . \'database.php\';" ' .
                    'AVANT la création de UserController, et que database.php définit bien $pdo.'
            );
        }

        $this->pdo = $pdo;
    }

    /**
     * Récupère un utilisateur par email, sous forme d'objet User.
     * Retourne null si introuvable.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE email = :email');
        $stmt->execute(['email' => $email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new User($data) : null;
    }

    /**
     * Récupère un utilisateur par id, sous forme d'objet User.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE user_id = :id');
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new User($data) : null;
    }

    /**
     * Inscription d'un nouvel utilisateur.
     * Retourne ['success' => bool, 'errors' => array]
     */
    public function register(string $firstName, string $lastName, string $email, string $motDePasse, string $confirmation): array
    {
        $errors = [];

        $firstName = trim($firstName);
        $lastName  = trim($lastName);
        $email     = trim($email);

        if ($firstName === '' || $lastName === '' || $email === '' || $motDePasse === '' || $confirmation === '') {
            $errors[] = 'Tous les champs sont obligatoires.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }

        if (strlen($motDePasse) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if ($motDePasse !== $confirmation) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }

        if (empty($errors) && $this->findByEmail($email) !== null) {
            $errors[] = 'Un compte existe déjà avec cet email.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = new User([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'password'   => password_hash($motDePasse, PASSWORD_BCRYPT),
            'role'       => 'user',
        ]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO user (first_name, last_name, email, password, role)
             VALUES (:first_name, :last_name, :email, :password, :role)'
        );

        $stmt->execute([
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'email'      => $user->getEmail(),
            'password'   => $user->getPassword(),
            'role'       => $user->getRole(),
        ]);

        return ['success' => true, 'errors' => []];
    }

    /**
     * Connexion d'un utilisateur.
     * Démarre la session si les identifiants sont valides.
     * Retourne ['success' => bool, 'errors' => array]
     */
    public function login(string $email, string $motDePasse): array
    {
        $email = trim($email);
        $user  = $this->findByEmail($email);

        if (!$user || !password_verify($motDePasse, $user->getPassword())) {
            return ['success' => false, 'errors' => ['Email ou mot de passe incorrect.']];
        }

        $_SESSION['user_id']        = $user->getId();
        $_SESSION['user_nom']       = $user->getFullName();
        $_SESSION['user_initials']  = strtoupper(
            mb_substr($user->getFirstName(), 0, 1) . mb_substr($user->getLastName(), 0, 1)
        );
        $_SESSION['user_role']      = $user->getRole();

        return ['success' => true, 'errors' => []];
    }

    /**
     * Déconnexion : vide et détruit la session.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                'PHPSESSID',
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Vérifie si l'utilisateur connecté est administrateur.
     */
    public function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? null) === 'admin';
    }
}
