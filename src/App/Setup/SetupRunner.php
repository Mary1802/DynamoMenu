<?php



declare(strict_types=1);



namespace App\Setup;



use App\Core\Application;

use PDOException;



/** Orchestration OOP de init_db.php / run_update.php. */

final class SetupRunner

{

    public function __construct(

        private readonly SetupGuard $guard,

        private readonly DatabaseInitializer $initializer,

        private readonly LegacyDatabaseUpdater $updater,

        private readonly Application $app,

    ) {

    }



    public static function fromApp(?Application $app = null): self

    {

        $app ??= Application::getInstance();



        return new self(

            SetupGuard::fromApp($app),

            DatabaseInitializer::fromApp($app),

            LegacyDatabaseUpdater::fromApp($app),

            $app,

        );

    }



    public function initDatabase(): void

    {

        $this->guard->requireAccess();



        try {

            $this->initializer->run();

            if (PHP_SAPI === 'cli') {

                SetupHtmlRenderer::cliSuccess('Base de données initialisée.');

            } else {

                SetupHtmlRenderer::initSuccess();

            }

        } catch (PDOException $e) {

            if (PHP_SAPI === 'cli') {

                fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);

                exit(1);

            }

            SetupHtmlRenderer::initError($e->getMessage());

        }

    }



    public function updateDatabase(): void

    {

        $this->guard->requireAccess();

        $this->app->staffAuth()->startSession();



        try {

            $log = $this->updater->run();

            if (PHP_SAPI === 'cli') {

                foreach ($log as $entry) {

                    echo $entry . PHP_EOL;

                }

            } else {

                SetupHtmlRenderer::updatePage($log);

            }

        } catch (PDOException $e) {

            if (PHP_SAPI === 'cli') {

                fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);

                exit(1);

            }

            SetupHtmlRenderer::connectionError($e->getMessage());

        }

    }

}


