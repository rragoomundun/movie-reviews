<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250110143128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE movie_actors (id SERIAL NOT NULL, movie_id INT NOT NULL, person_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_26EC6D908F93B6FC ON movie_actors (movie_id)');
        $this->addSql('CREATE INDEX IDX_26EC6D90217BBB47 ON movie_actors (person_id)');
        $this->addSql('ALTER TABLE movie_actors ADD CONSTRAINT FK_26EC6D908F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_actors ADD CONSTRAINT FK_26EC6D90217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE movie_actors DROP CONSTRAINT FK_26EC6D908F93B6FC');
        $this->addSql('ALTER TABLE movie_actors DROP CONSTRAINT FK_26EC6D90217BBB47');
        $this->addSql('DROP TABLE movie_actors');
    }
}
