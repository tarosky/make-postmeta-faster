/*!
 * Admin index management UI.
 *
 * @handle mpmf-admin
 * @deps wp-element,wp-components,wp-api-fetch,wp-i18n
 */

const { useState, useEffect, createRoot, render } = wp.element;
const {
	Button,
	Card,
	CardBody,
	CardHeader,
	Flex,
	FlexItem,
	Notice,
	Spinner,
	TabPanel,
} = wp.components;
const apiFetch = wp.apiFetch;
const { __ } = wp.i18n;

const API_BASE = 'mpmf/v1/index';

const TABLES = [
	{ name: 'postmeta', title: __( 'Post Meta', 'mpmf' ) },
	{ name: 'usermeta', title: __( 'User Meta', 'mpmf' ) },
	{ name: 'termmeta', title: __( 'Term Meta', 'mpmf' ) },
];

/**
 * Index status card.
 *
 * @param {Object}  props
 * @param {boolean} props.hasIndex
 * @param {Array}   props.indices
 */
function IndexStatusCard( { hasIndex, indices } ) {
	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Index Status', 'mpmf' ) }</h2>
			</CardHeader>
			<CardBody>
				<p>
					<span
						className={ `mpmf-status-indicator mpmf-status-indicator--${
							hasIndex ? 'active' : 'inactive'
						}` }
					/>
					{ hasIndex
						? __( 'Index is active.', 'mpmf' )
						: __( 'No index found.', 'mpmf' ) }
				</p>
				{ indices.length > 0 && (
					<table className="widefat striped mpmf-table">
						<thead>
							<tr>
								<th>{ __( 'Key Name', 'mpmf' ) }</th>
								<th>{ __( 'Column', 'mpmf' ) }</th>
								<th>{ __( 'Sub Part', 'mpmf' ) }</th>
								<th>{ __( 'Non Unique', 'mpmf' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ indices.map( ( row, i ) => (
								<tr key={ i }>
									<td>{ row.Key_name }</td>
									<td>{ row.Column_name }</td>
									<td>{ row.Sub_part ?? '—' }</td>
									<td>{ row.Non_unique }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</CardBody>
		</Card>
	);
}

/**
 * EXPLAIN results card.
 *
 * @param {Object} props
 * @param {Array}  props.explain
 * @param {Object} props.score
 */
function ExplainCard( { explain, score } ) {
	const total = score.filesort + score.temporary;
	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Query Performance', 'mpmf' ) }</h2>
			</CardHeader>
			<CardBody>
				<div
					className={ `mpmf-score mpmf-score--${
						total === 0 ? 'good' : 'bad'
					}` }
				>
					{ total === 0
						? __( 'Query is fast and good.', 'mpmf' )
						: __( 'Performance issues detected.', 'mpmf' ) }
					<span className="mpmf-score__detail">
						{ `filesort: ${ score.filesort }, temporary: ${ score.temporary }` }
					</span>
				</div>
				{ explain.length > 0 && (
					<table className="widefat striped mpmf-table">
						<thead>
							<tr>
								<th>{ __( 'Type', 'mpmf' ) }</th>
								<th>{ __( 'Possible Keys', 'mpmf' ) }</th>
								<th>{ __( 'Key', 'mpmf' ) }</th>
								<th>{ __( 'Rows', 'mpmf' ) }</th>
								<th>{ __( 'Extra', 'mpmf' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ explain.map( ( row, i ) => (
								<tr key={ i }>
									<td>{ row.type }</td>
									<td>{ row.possible_keys ?? '—' }</td>
									<td>{ row.key ?? '—' }</td>
									<td>{ row.rows ?? '—' }</td>
									<td>{ row.Extra ?? '—' }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</CardBody>
		</Card>
	);
}

/**
 * Key length settings card.
 *
 * @param {Object}   props
 * @param {Object}   props.keyLength
 * @param {Function} props.onSave
 * @param {boolean}  props.disabled
 * @param {string}   props.table
 */
function SettingsCard( { keyLength, onSave, disabled, table } ) {
	const [ metaKey, setMetaKey ] = useState( keyLength.meta_key );
	const [ metaValue, setMetaValue ] = useState( keyLength.meta_value );

	useEffect( () => {
		setMetaKey( keyLength.meta_key );
		setMetaValue( keyLength.meta_value );
	}, [ keyLength ] );

	const keyId = `mpmf-${ table }-meta-key-length`;
	const valueId = `mpmf-${ table }-meta-value-length`;

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Key Length Settings', 'mpmf' ) }</h2>
			</CardHeader>
			<CardBody>
				<p className="description">
					{ __(
						'These values affect the index key length. Changes take effect when you add or recreate the index.',
						'mpmf'
					) }
				</p>
				<Flex align="end" gap={ 4 }>
					<FlexItem>
						<label className="mpmf-label" htmlFor={ keyId }>
							{ __( 'Meta Key Length', 'mpmf' ) }
						</label>
						<input
							id={ keyId }
							type="number"
							className="mpmf-number-input"
							min={ 32 }
							max={ 255 }
							value={ metaKey }
							onChange={ ( e ) =>
								setMetaKey( Number( e.target.value ) )
							}
							disabled={ disabled }
						/>
					</FlexItem>
					<FlexItem>
						<label className="mpmf-label" htmlFor={ valueId }>
							{ __( 'Meta Value Length', 'mpmf' ) }
						</label>
						<input
							id={ valueId }
							type="number"
							className="mpmf-number-input"
							min={ 64 }
							value={ metaValue }
							onChange={ ( e ) =>
								setMetaValue( Number( e.target.value ) )
							}
							disabled={ disabled }
						/>
					</FlexItem>
					<FlexItem>
						<Button
							variant="secondary"
							disabled={ disabled }
							onClick={ () => onSave( metaKey, metaValue ) }
						>
							{ __( 'Save Settings', 'mpmf' ) }
						</Button>
					</FlexItem>
				</Flex>
			</CardBody>
		</Card>
	);
}

/**
 * Table-specific view. Each tab renders this with a different table.
 *
 * @param {Object} props
 * @param {string} props.table Table identifier (postmeta/usermeta/termmeta).
 */
function TableView( { table } ) {
	const [ loading, setLoading ] = useState( true );
	const [ actionInProgress, setActionInProgress ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ data, setData ] = useState( {
		has_index: false,
		indices: [],
		explain: [],
		explain_score: { filesort: 0, temporary: 0 },
		key_length: { meta_key: 255, meta_value: 64 },
	} );

	const path = `${ API_BASE }/${ table }`;
	const optionName = `mpmf-${ table }-key-length`;

	const fetchStatus = () => {
		setLoading( true );
		apiFetch( { path } )
			.then( ( res ) => {
				setData( res );
				setLoading( false );
			} )
			.catch( ( err ) => {
				setNotice( { type: 'error', message: err.message } );
				setLoading( false );
			} );
	};

	// eslint-disable-next-line react-hooks/exhaustive-deps
	useEffect( fetchStatus, [ table ] );

	const addIndex = ( update = false ) => {
		setActionInProgress( true );
		setNotice( null );
		apiFetch( { path, method: 'POST', data: { update } } )
			.then( ( res ) => {
				setNotice( { type: 'success', message: res.message } );
				fetchStatus();
			} )
			.catch( ( err ) => {
				setNotice( { type: 'error', message: err.message } );
			} )
			.finally( () => setActionInProgress( false ) );
	};

	const removeIndex = () => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__( 'Are you sure you want to remove the index?', 'mpmf' )
			)
		) {
			return;
		}
		setActionInProgress( true );
		setNotice( null );
		apiFetch( { path, method: 'DELETE' } )
			.then( ( res ) => {
				setNotice( { type: 'success', message: res.message } );
				fetchStatus();
			} )
			.catch( ( err ) => {
				setNotice( { type: 'error', message: err.message } );
			} )
			.finally( () => setActionInProgress( false ) );
	};

	const saveSettings = ( metaKey, metaValue ) => {
		setActionInProgress( true );
		setNotice( null );
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: { [ optionName ]: [ metaKey, metaValue ] },
		} )
			.then( () => {
				setNotice( {
					type: 'success',
					message: __( 'Settings saved.', 'mpmf' ),
				} );
				fetchStatus();
			} )
			.catch( ( err ) => {
				setNotice( { type: 'error', message: err.message } );
			} )
			.finally( () => setActionInProgress( false ) );
	};

	if ( loading ) {
		return (
			<div className="mpmf-loading">
				<Spinner />
			</div>
		);
	}

	const isDisabled = actionInProgress;

	return (
		<div className="mpmf-admin">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<IndexStatusCard
				hasIndex={ data.has_index }
				indices={ data.indices }
			/>
			<ExplainCard
				explain={ data.explain }
				score={ data.explain_score }
			/>
			<SettingsCard
				keyLength={ data.key_length }
				onSave={ saveSettings }
				disabled={ isDisabled }
				table={ table }
			/>

			<Flex className="mpmf-actions" gap={ 3 }>
				{ ! data.has_index ? (
					<FlexItem>
						<Button
							variant="primary"
							disabled={ isDisabled }
							isBusy={ actionInProgress }
							onClick={ () => addIndex( false ) }
						>
							{ __( 'Add Index', 'mpmf' ) }
						</Button>
					</FlexItem>
				) : (
					<>
						<FlexItem>
							<Button
								variant="primary"
								disabled={ isDisabled }
								isBusy={ actionInProgress }
								onClick={ () => addIndex( true ) }
							>
								{ __( 'Recreate Index', 'mpmf' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="secondary"
								isDestructive
								disabled={ isDisabled }
								onClick={ removeIndex }
							>
								{ __( 'Remove Index', 'mpmf' ) }
							</Button>
						</FlexItem>
					</>
				) }
				<FlexItem>
					<Button
						variant="tertiary"
						disabled={ isDisabled }
						onClick={ fetchStatus }
					>
						{ __( 'Refresh', 'mpmf' ) }
					</Button>
				</FlexItem>
			</Flex>
		</div>
	);
}

/**
 * Main application component.
 */
function App() {
	return (
		<TabPanel className="mpmf-tabs" tabs={ TABLES }>
			{ ( tab ) => <TableView table={ tab.name } /> }
		</TabPanel>
	);
}

// Mount application.
document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'mpmf-admin-root' );
	if ( ! root ) {
		return;
	}
	if ( createRoot ) {
		createRoot( root ).render( <App /> );
	} else {
		render( <App />, root );
	}
} );
