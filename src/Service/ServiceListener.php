<?php

namespace Wecamp\FlyingLiqourice\Service;

use Wecamp\FlyingLiqourice\Domain\Coords;
use Wecamp\FlyingLiqourice\Domain\Game;
use Wecamp\FlyingLiqourice\Domain\GameIdentifier;
use Wecamp\FlyingLiqourice\Storage\SqliteGameRepository;

class ServiceListener
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $id;

    private $repository;

    public function __construct($token, $id = '')
    {
        $this->token = strtolower($token);

        $this->id = $id;

        $dbh = new \PDO('sqlite:./data/games');
        $this->repository = new SqliteGameRepository($dbh);
    }

    public function run()
    {
        if (!empty($this->id)) {
            $identifier = GameIdentifier::fromString($this->id());
            $dbh = new \PDO('sqlite:./data/games');
            $repository = new SqliteGameRepository($dbh);

            $game = $repository->get($identifier);

            echo 'Re initializing game id: ' . $game->id() . PHP_EOL;
        }
        $tokenized = explode(' ', $this->token);
        $result = '';

        $command = trim($tokenized[0]);
        $argument = '';
        if (count($tokenized) == 2) {
            $argument = trim($tokenized[1]);
        }
        if (method_exists($this, $command)
            && $command !== 'run'
        ) {
            return $this->$command($argument);
        }

        throw new \InvalidArgumentException('Wrong command given');
    }

    public function id()
    {
        return $this->id;
    }

    private function repository()
    {
        return $this->repository;
    }

    protected function start($id = '')
    {
        if (strlen($id) !== 0) {
            $identifier = GameIdentifier::fromString($id);
            $game = $this->repository()->get($identifier);
            echo 'Game restarted: ' . $game->id() . PHP_EOL;
        } else {
            $game = Game::create();
            echo 'New game started: ' . $game->id() . PHP_EOL;
        }

        $this->id = $game->id();
        $result = 'STARTED ' . $game->id();

        $this->repository()->save($game);

        return $result;
    }

    protected function status()
    {
        $identifier = GameIdentifier::fromString($this->id());
        $game = $this->repository()->get($identifier);

        $result = 'STATUS ' . json_encode($game->toArray());
        echo 'Get game status: ' . $game->id() . PHP_EOL;
        return $result;
    }

    protected function quit()
    {
        $identifier = GameIdentifier::fromString($this->id());
        $game = $this->repository()->get($identifier);

        $result = 'LOST ' . $game->id();
        echo 'Quitting game ' . $game->id() . PHP_EOL;
        return $result;
    }

    protected function fire($location)
    {
        $identifier = GameIdentifier::fromString($this->id());
        $game = $this->repository()->get($identifier);
        $coordElements = explode('.', $location);
        $coords = Coords::fromArray(['x' => (int) $coordElements[0], 'y' => (int) $coordElements[1]]);
        $result = $game->fire($coords);
        $this->repository()->save($game);

        echo 'Firing on ' . $location . ' in game: ' . $game->id() . PHP_EOL;
        return $result;
    }
}
