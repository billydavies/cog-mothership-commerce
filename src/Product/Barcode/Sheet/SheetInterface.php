<?php

namespace Message\Mothership\Commerce\Product\Barcode\Sheet;

interface SheetInterface
{
	public function getLabelsPerPage();

	public function getName();

	public function getViewReference();

	public function getXCount();

	public function getYCount();

	public function getBarcodeHeight();

	public function getBarcodeWidth();
}