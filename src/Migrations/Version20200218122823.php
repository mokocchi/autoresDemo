<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200218122823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `role` (`id`, `name`) VALUES
                        (1, 'ROLE_AUTOR'),
                        (2, 'ROLE_USUARIO_APP'),
                        (3, 'ROLE_ADMIN');");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `role` WHERE `role`.`id` = 1;
                        DELETE FROM `role` WHERE `role`.`id` = 2;
                        DELETE FROM `role` WHERE `role`.`id` = 3;");
    }
}
