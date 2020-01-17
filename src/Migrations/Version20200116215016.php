<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200116215016 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tarea_opcional (planificacion_id INT NOT NULL, tarea_id INT NOT NULL, INDEX IDX_6D2EFEA44428E082 (planificacion_id), INDEX IDX_6D2EFEA46D5BDFE1 (tarea_id), PRIMARY KEY(planificacion_id, tarea_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tarea_inicial (planificacion_id INT NOT NULL, tarea_id INT NOT NULL, INDEX IDX_7ED58D8B4428E082 (planificacion_id), INDEX IDX_7ED58D8B6D5BDFE1 (tarea_id), PRIMARY KEY(planificacion_id, tarea_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tarea_opcional ADD CONSTRAINT FK_6D2EFEA44428E082 FOREIGN KEY (planificacion_id) REFERENCES planificacion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tarea_opcional ADD CONSTRAINT FK_6D2EFEA46D5BDFE1 FOREIGN KEY (tarea_id) REFERENCES tarea (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tarea_inicial ADD CONSTRAINT FK_7ED58D8B4428E082 FOREIGN KEY (planificacion_id) REFERENCES planificacion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tarea_inicial ADD CONSTRAINT FK_7ED58D8B6D5BDFE1 FOREIGN KEY (tarea_id) REFERENCES tarea (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tarea_opcional');
        $this->addSql('DROP TABLE tarea_inicial');
    }
}
