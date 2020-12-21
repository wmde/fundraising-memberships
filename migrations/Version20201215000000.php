<?php

declare( strict_types = 1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201215000000 extends AbstractMigration {

	public function up( Schema $schema ): void {
		$this->addSql( 'CREATE TABLE incentive ( `id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(32) NOT NULL , PRIMARY KEY (`id`), INDEX (`name`))' );
		$this->addSql( 'CREATE TABLE membership_incentive ( `membership_id` INT NOT NULL , `incentive_id` INT NOT NULL )' );
		$this->addSql( 'ALTER TABLE membership_incentive ADD CONSTRAINT `fk_membership` FOREIGN KEY (`membership_id`) REFERENCES `request`(`id`) ON DELETE CASCADE ON UPDATE CASCADE' );
		$this->addSql( 'ALTER TABLE membership_incentive ADD CONSTRAINT `fk_incentive` FOREIGN KEY (`incentive_id`) REFERENCES `incentive`(`id`) ON DELETE CASCADE ON UPDATE CASCADE ' );
		$this->addSql( 'INSERT INTO incentive VALUES (1, "tote_bag")' );
	}

	public function down( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE membership_incentive DROP FOREIGN KEY fk_membership;' );
		$this->addSql( 'ALTER TABLE membership_incentive DROP FOREIGN KEY fk_incentive' );
		$this->addSql( 'DROP TABLE membership_incentive' );
		$this->addSql( 'DROP TABLE incentive' );
	}

}
