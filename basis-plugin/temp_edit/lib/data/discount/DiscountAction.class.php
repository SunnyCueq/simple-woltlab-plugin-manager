<?php

namespace shrinkr\data\discount;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\UserInputException;
use wcf\system\file\upload\UploadFile;

/**
 * Executes discount-related actions.
 * 
 * Action class for performing operations on Discount database objects.
 * Handles AJAX requests for discount management, including favicon upload
 * and color validation.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.discount
 */
class DiscountAction extends AbstractDatabaseObjectAction
{
    /**
     * Required permissions for create action.
     *
     * @var    string[]
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageDiscounts'];

    /**
     * Required permissions for update action.
     *
     * @var    string[]
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageDiscounts'];

    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageDiscounts'];

    /**
     * Creates a new discount object.
     * 
     * Validates color values and handles favicon upload before creating
     * the discount entry in the database.
     *
     * @return  Discount  The created discount object
     * @throws  UserInputException  If color validation fails
     */
    public function create()
    {
        $this->validateColors();

        $discount = \call_user_func([$this->className, 'create'], $this->parameters['data']);
        $discountEditor = new DiscountEditor($discount);

        $updateData = [];
        if (isset($this->parameters['favicon']) && \is_array($this->parameters['favicon']) && \count($this->parameters['favicon'])) {
            $favicon = \reset($this->parameters['favicon']);
            if (!($favicon instanceof UploadFile)) {
                throw new \InvalidArgumentException("The parameter 'image' is no instance of '" . UploadFile::class . "', instance of '" . \get_class($favicon) . "' given.");
            }

            // save new image
            if (!$favicon->isProcessed()) {
                $fileName = $discount->discountID . '-' . $favicon->getFilename();

                \rename($favicon->getLocation(), WCF_DIR . 'images/discount/' . $fileName);
                $favicon->setProcessed(WCF_DIR . 'images/discount/' . $fileName);

                $updateData['favicon'] = $fileName;
            }
        }

        if (!empty($updateData)) {
            $discountEditor->update($updateData);
        }

        return $discount;
    }

    
    /**
     * @inheritDoc
     */
    public function update()
    {
        // Validate color values before updating
        $this->validateColors();

        parent::update();

        foreach ($this->getObjects() as $discount) {
            $updateData = [];

            // image
            if (isset($this->parameters['favicon_removedFiles']) && \is_array($this->parameters['favicon_removedFiles'])) {
                foreach ($this->parameters['favicon_removedFiles'] as $file) {
                    $updateData['favicon'] = null;
                    @\unlink($file->getLocation());
                }
            }

            if (isset($this->parameters['favicon']) && \is_array($this->parameters['favicon']) && \count($this->parameters['favicon'])) {
                $favicon = \reset($this->parameters['favicon']);
                if (!($favicon instanceof UploadFile)) {
                    throw new \InvalidArgumentException("The parameter 'image' is no instance of '" . UploadFile::class . "', instance of '" . \get_class($favicon) . "' given.");
                }

                // save new image
                if (!$favicon->isProcessed()) {
                    $fileName = $discount->discountID . '-' . $favicon->getFilename();

                    \rename($favicon->getLocation(), WCF_DIR . 'images/discount/' . $fileName);
                    $favicon->setProcessed(WCF_DIR . 'images/discount/' . $fileName);

                    $updateData['favicon'] = $fileName;
                }
            }

            if (!empty($updateData)) {
                $discount->update($updateData);
            }
        }
    }

    /**
     * Validates color values in the parameters.
     *
     * @throws UserInputException If any color value is invalid
     */
    protected function validateColors(): void
    {
        if (!isset($this->parameters['data'])) {
            return;
        }

        $colorFields = [
            'primaryColor',
            'secondaryColor',
            'primaryTextColor',
            'secondaryTextColor'
        ];

        foreach ($colorFields as $field) {
            if (isset($this->parameters['data'][$field])) {
                $color = $this->parameters['data'][$field];

                if (!empty($color) && !Discount::isValidColor($color)) {
                    throw new UserInputException($field, 'invalid');
                }
            }
        }
    }
}
