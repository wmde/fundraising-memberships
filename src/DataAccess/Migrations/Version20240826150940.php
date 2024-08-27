<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240826150940 extends AbstractMigration {
	public function getDescription(): string {
		return 'Increase postcode length to 16';
	}

	public function up( Schema $schema ): void {
		$schema->getTable( 'request' )->getColumn( 'plz' )->setLength( 16 );
	}

	public function down( Schema $schema ): void {
		$schema->getTable( 'request' )->getColumn( 'plz' )->setLength( 8 );
	}
}
