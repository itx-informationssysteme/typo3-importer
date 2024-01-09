<?php

namespace Itx\Importer\ViewHelpers;

use DateTime;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class DateIntervalViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('date1', 'object', 'DateTime 1', true);
        $this->registerArgument('date2', 'object', 'DateTime 2', true);
    }

    public static function renderStatic(array                     $arguments,
                                        \Closure                  $renderChildrenClosure,
                                        RenderingContextInterface $renderingContext)
    {
        /** @var DateTime $date1 */
        $date1 = $arguments['date1'];

        /** @var DateTime $date2 */
        $date2 = $arguments['date2'];

        if (!$date1 instanceof DateTime || !$date2 instanceof DateTime) {
            throw new \RuntimeException("Both arguments have to be of type \DateTime");
        }

        $duration = $date1->diff($date2);

        $durationFormat = '';

        if ($duration->h > 0) {
            $durationFormat .= '%h hours ';
        }

        if ($duration->i > 0) {
            $durationFormat .= '%i minutes ';
        }

        if ($duration->s > 0) {
            $durationFormat .= '%s seconds';
        }

        return $duration->format($durationFormat);
    }
}
