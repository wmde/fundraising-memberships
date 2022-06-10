<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

class ResultObject {
	private array $itemBuffer = [];
	private int $bufferIndex;
	private int $itemCount;
	private BoundedValue $IdRange;
	private BoundedValue $DateRange;

	/**
	 * @param int $bufferSize
	 * @param array $row
	 */
	public function __construct( private int $bufferSize, array $row ) {
		$this->itemBuffer = [ $row ];
		$this->bufferIndex = 0;
		$this->itemCount = 1;
		$this->IdRange = new BoundedValue( $row['id'] );
		$this->DateRange = new BoundedValue( $row['membershipDate'] );
	}

	public function add( array $row ): void {
		$this->itemBuffer[$this->bufferIndex] = $row;
		$this->itemCount++;
		$this->IdRange->set( $row['id'] );
		$this->DateRange->set( $row['membershipDate'] );
		$this->increaseBufferIndex();
	}

	public function getItemCount(): int {
		return $this->itemCount;
	}

	public function getItemSample(): array {
		return $this->itemBuffer;
	}

	private function increaseBufferIndex(): void {
		$this->bufferIndex++;
		if ( $this->bufferIndex > $this->bufferSize ) {
			$this->bufferIndex = 0;
		}
	}

	public function getDateRange(): BoundedValue {
		return $this->DateRange;
	}

}
