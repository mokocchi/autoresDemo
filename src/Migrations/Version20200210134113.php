<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200210134113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Startup data';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `estado` (`id`, `nombre`) VALUES (1, 'Público'), (2, 'Privado')");

        $this->addSql("INSERT INTO `idioma` (`id`, `nombre`, `code`) VALUES (1, 'Español', 'es'), (2, 'English (Inglés)', 'en'),"
            . "(3, '日本語 (Japonés)', 'ja')");

        $this->addSql("INSERT INTO `tipo_planificacion` (`id`, `nombre`) VALUES (1, 'Secuencial'), (2, 'Libre'), (3, 'Bifurcada')");

        $this->addSql("INSERT INTO `tipo_tarea` (`id`, `nombre`, `codigo`) VALUES (1, 'Simple', 'simple'),"
            . "(2, 'Ingresar texto', 'textInput'), (3, 'Ingresar número', 'numberInput'),(4, 'Sacar foto', 'cameraInput'),"
            . "(5, 'Elegir una opción', 'select'),(6, 'Opción múltiple', 'multiple'),(7, 'Contadores', 'counters'),"
            . "(8, 'Recolección', 'collect'),(9, 'Depósito', 'deposit'),(10, 'Localización', 'GPSInput')");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `estado` WHERE `estado`.`id` = 1");
        $this->addSql("DELETE FROM `estado` WHERE `estado`.`id` = 2");

        $this->addSql("DELETE FROM `idioma` WHERE `idioma`.`id` = 1");
        $this->addSql("DELETE FROM `idioma` WHERE `idioma`.`id` = 2");
        $this->addSql("DELETE FROM `idioma` WHERE `idioma`.`id` = 3");

        $this->addSql("DELETE FROM `tipo_planificacion` WHERE `tipo_planificacion`.`id` = 1");
        $this->addSql("DELETE FROM `tipo_planificacion` WHERE `tipo_planificacion`.`id` = 2");
        $this->addSql("DELETE FROM `tipo_planificacion` WHERE `tipo_planificacion`.`id` = 3");

        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 1");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 2");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 3");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 4");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 5");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 6");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 7");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 8");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 9");
        $this->addSql("DELETE FROM `tipo_tarea` WHERE `tipo_tarea`.`id` = 10");
    }
}
