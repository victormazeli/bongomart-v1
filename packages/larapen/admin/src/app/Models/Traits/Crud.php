<?php

namespace Larapen\Admin\app\Models\Traits;

trait Crud
{
	use HasEnumFields;
	use HasRelationshipFields;
	use HasUploadFields;
	use HasFakeFields;
	use HasTranslatableFields;
	
	/*
    |--------------------------------------------------------------------------
    | Translation Methods
    |--------------------------------------------------------------------------
    */
	
	
	/*
	|--------------------------------------------------------------------------
	| Methods for ALL models
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Check if the attribute exists
	 *
	 * @param $attr
	 * @return bool
	 */
	public function hasAttribute($attr)
	{
		return array_key_exists($attr, $this->attributes);
	}
	
	/**
	 * @param bool $xPanel
	 * @return string
	 */
	public function bulkDeletionBtn($xPanel = false)
	{
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.delete_selection') . '"';
		
		// Button
		$out = '';
		$out .= '<button name="deletion" class="bulk-action btn btn-danger shadow"' . $tooltip . '>';
		$out .= '<i class="fas fa-times"></i> ';
		$out .= trans('admin.delete');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param bool $xPanel
	 * @return string
	 */
	public function bulkActivationBtn($xPanel = false)
	{
		if (!isset($xPanel->model) || !in_array('active', $xPanel->model->getFillable())) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.activate_selection') . '"';
		
		// Button
		$out = '';
		$out .= '<button name="activation" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-on"></i> ';
		$out .= trans('admin.activate');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param bool $xPanel
	 * @return string
	 */
	public function bulkDeactivationBtn($xPanel = false)
	{
		if (!isset($xPanel->model) || !in_array('active', $xPanel->model->getFillable())) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.disable_selection') . '"';
		
		// Button
		$out = '';
		$out .= '<button name="deactivation" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-off"></i> ';
		$out .= trans('admin.disable');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param bool $xPanel
	 * @return string
	 */
	public function bulkApprovalBtn($xPanel = false)
	{
		if (
			!isset($xPanel->model)
			|| !in_array('reviewed', $xPanel->model->getFillable())
			|| !config('settings.single.listings_review_activation')
		) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.approve_selection') . '"';
		
		// Button
		$out = '';
		$out .= '<button name="approval" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-on"></i> ';
		$out .= trans('admin.approve');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param bool $xPanel
	 * @return string
	 */
	public function bulkDisapprovalBtn($xPanel = false)
	{
		if (
			!isset($xPanel->model)
			|| !in_array('reviewed', $xPanel->model->getFillable())
			|| !config('settings.single.listings_review_activation')
		) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.disapprove_selection') . '"';
		
		// Button
		$out = '';
		$out .= '<button name="disapproval" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-off"></i> ';
		$out .= trans('admin.disapprove');
		$out .= '</button>';
		
		return $out;
	}
}