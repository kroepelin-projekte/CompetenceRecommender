<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ilCompetenceRecommenderAlgorithmTest extends TestCase
{
	public function testOfZero(): void
	{
		$this->assertEquals(0, ilCompetenceRecommenderAlgorithm::score(0, 0, 0, 0, 0, 0));
	}

	public function testOnlyOneDate(): void
	{
		$this->assertEquals(2, ilCompetenceRecommenderAlgorithm::score(1, 0, 0, 2, 0, 0));
		$this->assertEquals(2, ilCompetenceRecommenderAlgorithm::score(4, 0, 0, 2, 0, 0));
		$this->assertEquals(2, ilCompetenceRecommenderAlgorithm::score(0, 1, 0, 0, 2, 0));
		$this->assertEquals(2, ilCompetenceRecommenderAlgorithm::score(0, 0, 1, 0, 0, 2));
	}

	public function testTwoDates(): void
	{
		$this->assertEquals(3, ilCompetenceRecommenderAlgorithm::score(1, 1, 0, 2, 4, 0));
		$this->assertEquals(2.33, round(ilCompetenceRecommenderAlgorithm::score(1, 5, 0, 2, 4, 0), 2));
		$this->assertEquals(3.5, ilCompetenceRecommenderAlgorithm::score(1, 0, 1, 2, 0, 5));
		$this->assertEquals(2.25, ilCompetenceRecommenderAlgorithm::score(1, 0, 3, 2, 0, 3));
		$this->assertEquals(4.43, round(ilCompetenceRecommenderAlgorithm::score(6, 0, 1, 1, 0, 5), 2));
		$this->assertEquals(2.5, ilCompetenceRecommenderAlgorithm::score(0, 1, 1, 0, 2, 3));
		$this->assertEquals(4.71, round(ilCompetenceRecommenderAlgorithm::score(0, 13, 1, 0, 1, 5), 2));
	}

	public function testAllDate(): void
	{
		$this->assertEquals(1, ilCompetenceRecommenderAlgorithm::score(1, 1, 1, 1, 1, 1));
		$this->assertEquals(3, ilCompetenceRecommenderAlgorithm::score(100, 100, 100, 2, 3, 4));
		$this->assertEquals(1.44, round(ilCompetenceRecommenderAlgorithm::score(4, 1, 4, 1, 2, 1), 2));
		$this->assertEquals(2.57, round(ilCompetenceRecommenderAlgorithm::score(1, 2, 4, 3, 3, 1), 2));
	}

	public function testOldDate(): void
	{
		$this->assertEquals(1, ilCompetenceRecommenderAlgorithm::score(3, 3, 3, 1, 1, 1));
		$this->assertEquals(3, ilCompetenceRecommenderAlgorithm::score(100, 100, 100, 2, 3, 4));
		$this->assertEquals(1.44, round(ilCompetenceRecommenderAlgorithm::score(10, 7, 10, 1, 2, 1), 2));
		$this->assertEquals(2.57, round(ilCompetenceRecommenderAlgorithm::score(2, 3, 5, 3, 3, 1), 2));
	}

	public function testEdgeCasesOneDate(): void
	{
		$this->assertEquals(0, ilCompetenceRecommenderAlgorithm::score(1, 0, 0, 0, 0, 0));
		$this->assertEquals(0, ilCompetenceRecommenderAlgorithm::score(0, 0, 0, 2, 0, 0));
	}

	public function testEdgeCasesTwoDates(): void
	{
		$this->assertEquals(2, ilCompetenceRecommenderAlgorithm::score(1, 1, 0, 2, 0, 0));
		$this->assertEquals(2, ilCompetenceRecommenderAlgorithm::score(1, 0, 0, 2, 5, 0));
	}

	public function testEdgeCasesThreeDates(): void
	{
		$this->assertEquals(2.5, ilCompetenceRecommenderAlgorithm::score(1, 1, 0, 2, 3, 4));
		$this->assertEquals(2.33, round(ilCompetenceRecommenderAlgorithm::score(1, 2, 3, 2, 3, 0), 2));
	}

	public function testDropOut(): void
	{
		#dropout is 20
		$this->assertEquals(2.91, round(ilCompetenceRecommenderAlgorithm::score(1, 1, 20, 2, 4, 1,20), 2));
		$this->assertEquals(3, ilCompetenceRecommenderAlgorithm::score(1, 1, 23, 2, 4, 1, 20));
		$this->assertEquals(3, round(ilCompetenceRecommenderAlgorithm::score(24, 24, 24, 2, 4, 3, 20), 2));
	}
}