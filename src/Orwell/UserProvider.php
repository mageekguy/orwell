<?php

namespace Orwell;

use
	Symfony\Component\Security\Core\User\UserProviderInterface,
	Symfony\Component\Security\Core\User\UserInterface,
	Symfony\Component\Security\Core\User\User,
	Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
	Doctrine\DBAL\Connection,
	Doctrine\DBAL\Schema\Table
;

class UserProvider implements UserProviderInterface
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadUserByUsername($username)
    {
        $stmt = $this->connection->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower($username)));

        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return new User($user['username'], $user['password'], explode(',', $user['roles']), true, true, true, true);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return ($class === 'Symfony\Component\Security\Core\User\User');
    }
}
