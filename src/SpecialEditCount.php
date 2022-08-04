<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 */

namespace MediaWiki\Extension\EditCount;

use FormSpecialPage;
use Html;
use MediaWiki\MediaWikiServices;
use Status;

class SpecialEditCount extends FormSpecialPage {

	public function __construct() {
		parent::__construct( 'EditCount', 'Edit Count' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getFormFields() {
		return [
			'user' => [
				'type' => 'user',
				'exists' => true,
				'label-message' => 'editcount-user',
				'required' => true
			]
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'users';
	}

	/**
	 * @param array $data
	 */
	public function onSubmit( array $data ) {

		$userFactory = MediaWikiServices::getInstance()->getUserFactory();

		$user = $userFactory->newFromName( $data['user'] );
		$result = EditCountQuery::queryAllNamespaces( $user );

		// add heading
		$this->getOutput()->addHTML(Html::element(
			'h2',
			[ 'id' => 'editcount-queryresult' ],
			$this->msg( 'editcount-resulttitle' )->params( $user->getName() )->parse()
		) );

		$this->makeTable( $result );

		return Status::newGood();
	}

	/**
	 * @param array $data
	 */
	protected function makeTable( $data ) {
		$lang = $this->getLanguage();

		$out = Html::openElement(
			'table',
			[ 'class' => 'mw-editcounttable wikitable' ]
		) . "\n";
		$out .= Html::openElement( 'thead' ) .
			Html::openElement( 'tr', [ 'class' => 'mw-editcounttable-header' ] ) .
			Html::element( 'th', [], $this->msg( 'namespace' )->text() ) .
			Html::element( 'th', [], $this->msg( 'editcount-count')->text() ) .
			Html::element( 'th', [], $this->msg( 'editcount-percentage' )->text() ) .
			Html::closeElement( 'tr' ) .
			Html::closeElement( 'thead' ) .
			Html::openElement( 'tbody' );

		foreach ( $data as $ns => $count ) {
			if ( is_int( $ns ) ) {
				if ( $ns == NS_MAIN ) {
					$nsName = $this->msg( 'blanknamespace' );
				} else {
					$converter = MediaWikiServices::getInstance()->getLanguageConverterFactory()
						->getLanguageConverter( $lang );
					$nsName = $converter->convertNamespace( $ns );
				}
				$out .= Html::openElement( 'tr', [ 'class' => 'mw-editcounttable-row' ] ) .
					Html::element(
						'td',
						[ 'class' => 'mw-editcounttable-ns' ],
						$nsName
					) .
					Html::element(
						'td',
						[ 'class' => 'mw-editcounttable-count' ],
						$lang->formatNum( $count )
					) .
					Html::element(
						'td',
						[ 'class' => 'mw-editcounttable-percentage' ],
						wfPercent( $count / $data['all'] * 100 )
					)
					Html::closeElement( 'tr' );
			} else {
				$nsName = $this->msg( 'editcount-all-namespaces' );
				$out .= Html::openElement( 'tr', [ 'class' => 'mw-editcounttable-footer' ] ) .
					Html::element( 'th', [], $nsName ) .
					Html::element(
						'th',
						[ 'class' => 'mw-editcounttable-count' ],
						$lang->formatNum( $count )
					) .
					Html::element(
						'th',
						[ 'class' => 'mw-editcounttable-percentage' ],
						wfPercent( 100 )
					) .
					Html::closeElement( 'tr' );
			}
		}

		$out .= Html::closeElement( 'tbody' ) .
			Html::closeElement( 'table' );

		$this->getOutput()->addHTML( $out );
	}

	/**
	 * @inheritDoc
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}
}