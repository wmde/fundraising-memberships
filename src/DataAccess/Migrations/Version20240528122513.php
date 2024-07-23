<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240528122513 extends AbstractMigration {
	public function getDescription(): string {
		return 'Update indexes for address information search';
	}

	public function up( Schema $schema ): void {
		$table = $schema->getTable( 'request' );
		$table->renameIndex( 'm_payment_id', 'idx_m_payment_id' );
		$table->dropIndex( 'm_ort' );
		$table->dropIndex( 'm_email' );
		$table->dropIndex( 'm_name' );
		$table->addIndex( [ 'vorname' ], 'idx_m_firstname' );
		$table->addIndex( [ 'nachname' ], 'idx_m_lastname' );
		$table->addIndex( [ 'strasse' ], 'idx_m_street' );
		$table->addIndex( [ 'plz' ], 'idx_m_postcode' );
		$table->addIndex( [ 'ort' ], 'idx_m_city' );
		$table->addIndex( [ 'email' ], 'idx_m_email' );
	}

	public function down( Schema $schema ): void {
		$table = $schema->getTable( 'requests' );
		$table->renameIndex( 'idx_m_payment_id', 'm_payment_id' );
		$table->dropIndex( 'idx_m_ort' );
		$table->dropIndex( 'idx_m_email' );
		$table->dropIndex( 'idx_m_firstname' );
		$table->dropIndex( 'idx_m_lastname' );
		$table->dropIndex( 'idx_m_street' );
		$table->dropIndex( 'idx_m_postcode' );
		$table->addIndex( [ 'name' ], 'm_name', [ 'fulltext' ] );
		$table->addIndex( [ 'ort' ], 'm_ort', [ 'fulltext' ] );
		$table->addIndex( [ 'email' ], 'm_email', [ 'fulltext' ] );
	}
}
