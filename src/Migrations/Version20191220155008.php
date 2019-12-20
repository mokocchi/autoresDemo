<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191220155008 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tipo_tarea (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dominio (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE actividad (id INT AUTO_INCREMENT NOT NULL, idioma_id INT DEFAULT NULL, dominio_id INT DEFAULT NULL, planificacion_id INT DEFAULT NULL, nombre VARCHAR(255) NOT NULL, objetivo VARCHAR(255) NOT NULL, INDEX IDX_8DF2BD06DEDC0611 (idioma_id), INDEX IDX_8DF2BD06B105BE34 (dominio_id), INDEX IDX_8DF2BD064428E082 (planificacion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tarea (id INT AUTO_INCREMENT NOT NULL, dominio_id INT DEFAULT NULL, tipo_id INT DEFAULT NULL, nombre VARCHAR(255) NOT NULL, consigna VARCHAR(255) NOT NULL, extra JSON DEFAULT NULL, INDEX IDX_3CA05366B105BE34 (dominio_id), INDEX IDX_3CA05366A9276E6C (tipo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE planificacion (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE idioma (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, code VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD06DEDC0611 FOREIGN KEY (idioma_id) REFERENCES idioma (id)');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD06B105BE34 FOREIGN KEY (dominio_id) REFERENCES dominio (id)');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD064428E082 FOREIGN KEY (planificacion_id) REFERENCES planificacion (id)');
        $this->addSql('ALTER TABLE tarea ADD CONSTRAINT FK_3CA05366B105BE34 FOREIGN KEY (dominio_id) REFERENCES dominio (id)');
        $this->addSql('ALTER TABLE tarea ADD CONSTRAINT FK_3CA05366A9276E6C FOREIGN KEY (tipo_id) REFERENCES tipo_tarea (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tarea DROP FOREIGN KEY FK_3CA05366A9276E6C');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD06B105BE34');
        $this->addSql('ALTER TABLE tarea DROP FOREIGN KEY FK_3CA05366B105BE34');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD064428E082');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD06DEDC0611');
        $this->addSql('DROP TABLE tipo_tarea');
        $this->addSql('DROP TABLE dominio');
        $this->addSql('DROP TABLE actividad');
        $this->addSql('DROP TABLE tarea');
        $this->addSql('DROP TABLE planificacion');
        $this->addSql('DROP TABLE idioma');
    }
}
