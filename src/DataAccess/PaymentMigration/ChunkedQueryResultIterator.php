<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This class takes a Doctrine DBAL query builder with a SELECT query and returns a result iterator.
 * Use this class to iterate over large result sets that would not fit in memory.
 *
 * Internally, this class will modify the query to limit the number of rows.
 * This means it will execute the same query, but with different, increasing offsets on the data.
 */
class ChunkedQueryResultIterator implements \IteratorAggregate {

	/**
	 * @param QueryBuilder $qb
	 * @param string $offsetField The database field name to use for the "greater than" offset comparison.
	 *          Must be an integer field. The primary key is the best field to use for this.
	 *          If the field values are non-unique, you might encounter dropped rows, depending on
	 *          the number of equal values and $chunkSize.
	 *          The offset field does not have to be part of the selected fields.
	 * @param int $chunkSize How many rows each query result should have.
	 *          It's your choice to find the right tradeoff between CPU and memory.
	 *          The lower the value is, the more queries this iterator will issue.
	 *          Try to keep it as big as possible without breaking the memory limit.
	 * @param int $maxOffset Return result sets up to this offset value, including the maximum value.
	 *          Usually the result of a "SELECT MAX(offset_field) FROM your_table" query
	 * @param int $offsetStart Minimum offset value (exclusive).
	 */
	public function __construct(
		private QueryBuilder $qb,
		private string $offsetField,
		private int $chunkSize,
		private int $maxOffset,
		private int $offsetStart = 0 ) {
		if ( $qb->getType() !== QueryBuilder::SELECT ) {
			throw new \LogicException( 'This iterator is for iterating SELECT results' );
		}
	}

	public function getIterator(): \Traversable {
		$this->qb->setMaxResults( $this->chunkSize );
		$this->qb->andWhere( $this->qb->expr()->gt( $this->offsetField, ':offset' ) );

		for ( $offset = $this->offsetStart; $offset <= $this->maxOffset; $offset += $this->chunkSize ) {
			if ( $this->isLastChunk( $offset ) ) {
				// Avoid fetching too many rows for the last chunk
				$remainingRowCount = $this->maxOffset - $offset;
				if ( $remainingRowCount === 0 ) {

					return;
				}
				$this->qb->setMaxResults( $remainingRowCount );
			}

			$this->qb->setParameter( 'offset', $offset );
			$dbResult = $this->qb->executeQuery();

			foreach ( $dbResult->iterateAssociative() as $row ) {
				yield $row;
			}
		}
	}

	private function isLastChunk( int $offset ): bool {
		return $offset + $this->chunkSize > $this->maxOffset;
	}
}
