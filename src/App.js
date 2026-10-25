import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	ColorPicker,
	Flex,
	FlexItem,
	Notice,
	RangeControl,
	SelectControl,
	Spinner,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { check, code, desktop, megaphone } from '@wordpress/icons';

// Default values keep the plugin usable on first install and provide a fallback
// shape when older saved settings are merged in from the REST API.
const defaults = {
	enabled: true,
	mode: 'development',
	customMode: '',
	message: __('This website is currently under development.', 'development-mode-indicator'),
	displayType: 'admin_bar',
	adminBar: {
		icon: 'admin-tools',
		backgroundColor: '#d63638',
		textColor: '#ffffff',
	},
	watermark: {
		position: 'center',
		backendDisplay: 'watermark',
		adminBarTextColor: '#ffffff',
		opacity: 0.15,
		rotation: -20,
		fontSize: 48,
		textColor: '#d63638',
		background: '',
	},
	border: {
		position: 'bottom',
		primaryColor: '#d63638',
		secondaryColor: '#ffffff',
		height: 8,
		patternSize: 20,
	},
};

const modes = [
	[
		'development',
		__('Development', 'development-mode-indicator'),
		__('This website is currently under development.', 'development-mode-indicator'),
	],
	['staging', __('Staging', 'development-mode-indicator'), __('This is a staging website.', 'development-mode-indicator')],
	['testing', __('Testing', 'development-mode-indicator'), __('This website is currently being tested.', 'development-mode-indicator')],
	['qa', __('QA', 'development-mode-indicator'), __('This website is currently undergoing QA testing.', 'development-mode-indicator')],
	[
		'maintenance',
		__('Maintenance', 'development-mode-indicator'),
		__('This website is currently under maintenance.', 'development-mode-indicator'),
	],
	['custom', __('Custom', 'development-mode-indicator'), __('Custom development environment.', 'development-mode-indicator')],
];

const displays = [
	{
		id: 'admin_bar',
		title: __('Admin Bar', 'development-mode-indicator'),
		description: __('A compact status marker in the WordPress admin bar.', 'development-mode-indicator'),
		icon: code,
	},
	{
		id: 'watermark',
		title: __('Watermark', 'development-mode-indicator'),
		description: __('A subtle, fixed label over the page.', 'development-mode-indicator'),
		icon: megaphone,
	},
	{
		id: 'border',
		title: __('Border', 'development-mode-indicator'),
		description: __('A persistent striped warning edge.', 'development-mode-indicator'),
		icon: desktop,
	},
];

// Merge stored values with the default schema so legacy entries and partial
// payloads still resolve to a complete settings object.
const mergeSettings = (value) => {
	const watermark = value?.watermark || {};

	return {
		...defaults,
		...value,
		adminBar: { ...defaults.adminBar, ...(value?.adminBar || {}) },
		watermark: {
			...defaults.watermark,
			...watermark,
			backendDisplay: watermark.backendDisplay || (watermark.showAdminBar ? 'admin_bar' : defaults.watermark.backendDisplay),
		},
		border: { ...defaults.border, ...(value?.border || {}) },
	};
};

const modeLabel = (settings) => {
	return settings.mode === 'custom'
		? settings.customMode || __('Custom', 'development-mode-indicator')
		: modes.find((mode) => mode[0] === settings.mode)?.[1];
};

const updateNested = (settings, section, key, value) => {
	return { ...settings, [section]: { ...settings[section], [key]: value } };
};

const DisplayCard = ({ display, selected, onSelect, settings }) => {
	return (
		<button className={`dmi-display-card${selected ? ' is-selected' : ''}`} onClick={onSelect} type='button' aria-pressed={selected}>
			<Flex align='center' justify='space-between'>
				<FlexItem>
					<strong>{display.title}</strong>
				</FlexItem>
				{selected && (
					<FlexItem>
						<span className='dmi-check'>
							<span className='screen-reader-text'>{__('Selected', 'development-mode-indicator')}</span>✓
						</span>
					</FlexItem>
				)}
			</Flex>
			<div
				className={`dmi-mini-preview dmi-mini-preview--${display.id}`}
				style={
					display.id === 'border'
						? {
								'--dmi-mini-primary': settings.border.primaryColor,
								'--dmi-mini-secondary': settings.border.secondaryColor,
						  }
						: undefined
				}
			>
				{display.id === 'admin_bar' && (
					<>
						<span>WP Admin</span>
						<b>{modeLabel(settings)}</b>
					</>
				)}
				{display.id === 'watermark' && <b>{modeLabel(settings)}</b>}
				{display.id === 'border' && <span />}
			</div>
			<p>{display.description}</p>
		</button>
	);
};

// The preview is a lightweight representation of the real indicator output so
// the admin can adjust styling without leaving the settings screen.
const Preview = ({ settings }) => {
	const label = modeLabel(settings);
	return (
		<div className='dmi-preview' aria-label={__('Live indicator preview', 'development-mode-indicator')}>
			<div className='dmi-preview__chrome'>
				<span /> <span /> <span /> <strong>{__('Example Website', 'development-mode-indicator')}</strong>
			</div>
			<div className='dmi-preview__content'>
				<span className='dmi-preview__eyebrow'>{__('Live preview', 'development-mode-indicator')}</span>
				<h3>{__('A calm place to build.', 'development-mode-indicator')}</h3>
				<p>{__('Your indicator appears on top of the site without changing its content.', 'development-mode-indicator')}</p>
			</div>
			{settings.enabled &&
				(settings.displayType === 'admin_bar' ||
					(settings.displayType === 'watermark' && settings.watermark.backendDisplay === 'admin_bar')) && (
					<div className='dmi-preview__admin-bar'>
						<span>{label}</span>
						<small>{settings.message}</small>
					</div>
				)}
			{settings.enabled && settings.displayType === 'watermark' && (
				<div
					className='dmi-preview__watermark'
					style={{
						color: settings.watermark.textColor,
						opacity: settings.watermark.opacity,
						fontSize: `${settings.watermark.fontSize / 2}px`,
						transform: `rotate(${settings.watermark.rotation}deg)`,
					}}
				>
					{label}
				</div>
			)}
			{settings.enabled && settings.displayType === 'border' && (
				<div
					className={`dmi-preview__border dmi-preview__border--${settings.border.position}`}
					style={{
						'--dmi-preview-primary': settings.border.primaryColor,
						'--dmi-preview-secondary': settings.border.secondaryColor,
						'--dmi-preview-size': `${settings.border.height * 1.5}px`,
					}}
				>
					<span>{settings.message}</span>
				</div>
			)}
		</div>
	);
};

const ColorSetting = ({ label, value, onChange }) => {
	const [isOpen, setIsOpen] = useState(false);

	return (
		<div className='dmi-color-setting'>
			<div className='dmi-color-setting__row'>
				<div className='dmi-color-setting__field'>
					<TextControl label={label} value={value} onChange={onChange} />
				</div>
				<Button className='dmi-color-setting__button' variant='secondary' isSmall onClick={() => setIsOpen((current) => !current)}>
					{isOpen ? __('Hide color', 'development-mode-indicator') : __('Choose color', 'development-mode-indicator')}
				</Button>
			</div>
			{isOpen && <ColorPicker color={value} onChangeComplete={(color) => onChange(color.hex)} disableAlpha />}
		</div>
	);
};

// Main admin screen: fetch saved values, render the configuration panels, and
// persist any updates back to the WordPress settings API.
const App = () => {
	const [settings, setSettings] = useState(defaults);
	const [loading, setLoading] = useState(true);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		apiFetch({ path: '/development-mode-indicator/v1/settings' })
			.then((response) => setSettings(mergeSettings(response)))
			.catch(() =>
				setNotice({
					status: 'error',
					text: __('Could not load the saved settings.', 'development-mode-indicator'),
				}),
			)
			.finally(() => setLoading(false));
	}, []);

	const set = (next) => setSettings((current) => (typeof next === 'function' ? next(current) : next));

	const changeMode = (mode) => {
		const nextMessage = modes.find((item) => item[0] === mode)?.[2];
		set((current) => ({
			...current,
			mode,
			message: current.message === modes.find((item) => item[0] === current.mode)?.[2] ? nextMessage : current.message,
		}));
	};

	// Save the full nested settings object through the custom REST option field.
	const save = () => {
		setSaving(true);
		apiFetch({
			path: '/development-mode-indicator/v1/settings',
			method: 'POST',
			data: { settings },
		})
			.then((response) => {
				const nextSettings = mergeSettings(response);
				setSettings(nextSettings);
				setNotice({
					status: 'success',
					text: __('Changes saved. Reload the page to view the changes.', 'development-mode-indicator'),
				});
				window.scrollTo({ top: 0, behavior: 'smooth' });
			})
			.catch(() =>
				setNotice({
					status: 'error',
					text: __('Changes could not be saved.', 'development-mode-indicator'),
				}),
			)
			.finally(() => setSaving(false));
	};

	if (loading) {
		return (
			<div className='dmi-loading'>
				<Spinner /> {__('Loading Development Mode...', 'development-mode-indicator')}
			</div>
		);
	}

	return (
		<div className='dmi-shell'>
			<header className='dmi-hero'>
				<div>
					<span className='dmi-kicker'>{__('Website environment', 'development-mode-indicator')}</span>
					<h1>{__('Development Mode', 'development-mode-indicator')}</h1>
					<p>{__('Make the current environment impossible to mistake for production.', 'development-mode-indicator')}</p>
				</div>
				<ToggleControl
					label={__('Indicator enabled', 'development-mode-indicator')}
					checked={settings.enabled}
					onChange={(enabled) => set({ ...settings, enabled })}
				/>
			</header>
			{notice && (
				<Notice status={notice.status} onRemove={() => setNotice(null)} isDismissible>
					{notice.text}
				</Notice>
			)}
			<main className='dmi-layout'>
				<section className='dmi-column dmi-column--main'>
					<Card>
						<CardBody>
							<span className='dmi-section-label'>{__('Configuration', 'development-mode-indicator')}</span>
							<h2>{__('Describe this environment', 'development-mode-indicator')}</h2>
							<SelectControl
								label={__('Development mode', 'development-mode-indicator')}
								value={settings.mode}
								options={modes.map((mode) => ({
									value: mode[0],
									label: mode[1],
								}))}
								onChange={changeMode}
							/>
							{settings.mode === 'custom' && (
								<TextControl
									label={__('Custom mode name', 'development-mode-indicator')}
									value={settings.customMode}
									onChange={(customMode) => set({ ...settings, customMode })}
								/>
							)}
							<TextareaControl
								label={__('Message', 'development-mode-indicator')}
								value={settings.message}
								onChange={(message) => set({ ...settings, message })}
								help={__('This text appears alongside the mode label.', 'development-mode-indicator')}
							/>
							{settings.displayType === 'watermark' && (
								<SelectControl
									label={__('Backend display', 'development-mode-indicator')}
									value={settings.watermark.backendDisplay}
									options={[
										['watermark', __('Watermark', 'development-mode-indicator')],
										['admin_bar', __('Show info in Admin Bar only', 'development-mode-indicator')],
									].map((option) => ({
										value: option[0],
										label: option[1],
									}))}
									onChange={(value) => set(updateNested(settings, 'watermark', 'backendDisplay', value))}
								/>
							)}
							{settings.displayType === 'border' && (
								<SelectControl
									label={__('Border position', 'development-mode-indicator')}
									value={settings.border.position}
									options={[
										['top', __('Top', 'development-mode-indicator')],
										['left', __('Left', 'development-mode-indicator')],
										['right', __('Right', 'development-mode-indicator')],
										['bottom', __('Bottom', 'development-mode-indicator')],
									].map((option) => ({ value: option[0], label: option[1] }))}
									onChange={(value) => set(updateNested(settings, 'border', 'position', value))}
								/>
							)}
						</CardBody>
					</Card>
					<Card>
						<CardBody>
							<span className='dmi-section-label'>{__('Live preview', 'development-mode-indicator')}</span>
							<h2>{__('See it in context', 'development-mode-indicator')}</h2>
							<Preview settings={settings} />
						</CardBody>
					</Card>
				</section>
				<aside className='dmi-column dmi-column--side'>
					<Card>
						<CardBody>
							<span className='dmi-section-label'>{__('Display method', 'development-mode-indicator')}</span>
							<h2>{__('Choose a signal', 'development-mode-indicator')}</h2>
							<div className='dmi-display-list'>
								{displays.map((display) => (
									<DisplayCard
										key={display.id}
										display={display}
										selected={settings.displayType === display.id}
										settings={settings}
										onSelect={() => set({ ...settings, displayType: display.id })}
									/>
								))}
							</div>
						</CardBody>
					</Card>
					<Card>
						<CardBody>
							<span className='dmi-section-label'>{__('Fine tune', 'development-mode-indicator')}</span>
							<h2>{__('Display settings', 'development-mode-indicator')}</h2>
							{settings.displayType === 'admin_bar' && (
								<>
									<TextControl
										label={__('Dashicon name', 'development-mode-indicator')}
										value={settings.adminBar.icon}
										onChange={(icon) => set(updateNested(settings, 'adminBar', 'icon', icon))}
										help={__('Use a Dashicon slug such as admin-tools.', 'development-mode-indicator')}
									/>
									<ColorSetting
										label={__('Background color', 'development-mode-indicator')}
										value={settings.adminBar.backgroundColor}
										onChange={(value) => set(updateNested(settings, 'adminBar', 'backgroundColor', value))}
									/>
									<ColorSetting
										label={__('Text color', 'development-mode-indicator')}
										value={settings.adminBar.textColor}
										onChange={(value) => set(updateNested(settings, 'adminBar', 'textColor', value))}
									/>
								</>
							)}
							{settings.displayType === 'watermark' && (
								<>
									<SelectControl
										label={__('Position', 'development-mode-indicator')}
										value={settings.watermark.position}
										options={['center', 'bottom-right', 'bottom-left', 'top-right'].map((value) => ({
											value,
											label: value.replace('-', ' '),
										}))}
										onChange={(value) => set(updateNested(settings, 'watermark', 'position', value))}
									/>
									<RangeControl
										label={__('Opacity', 'development-mode-indicator')}
										value={settings.watermark.opacity}
										min={0.01}
										max={1}
										step={0.01}
										onChange={(value) => set(updateNested(settings, 'watermark', 'opacity', value))}
									/>
									<RangeControl
										label={__('Rotation', 'development-mode-indicator')}
										value={settings.watermark.rotation}
										min={-45}
										max={45}
										onChange={(value) => set(updateNested(settings, 'watermark', 'rotation', value))}
									/>
									<RangeControl
										label={__('Font size', 'development-mode-indicator')}
										value={settings.watermark.fontSize}
										min={16}
										max={120}
										onChange={(value) => set(updateNested(settings, 'watermark', 'fontSize', value))}
									/>
									<ColorSetting
										label={__('Text color', 'development-mode-indicator')}
										value={settings.watermark.textColor}
										onChange={(value) => set(updateNested(settings, 'watermark', 'textColor', value))}
									/>
									{settings.watermark.backendDisplay === 'admin_bar' && (
										<>
											<ColorSetting
												label={__('Admin Bar background color', 'development-mode-indicator')}
												value={settings.watermark.adminBarBackgroundColor}
												onChange={(value) => set(updateNested(settings, 'watermark', 'adminBarBackgroundColor', value))}
											/>
											<ColorSetting
												label={__('Admin Bar text color', 'development-mode-indicator')}
												value={settings.watermark.adminBarTextColor}
												onChange={(value) => set(updateNested(settings, 'watermark', 'adminBarTextColor', value))}
											/>
										</>
									)}
								</>
							)}
							{settings.displayType === 'border' && (
								<>
									<RangeControl
										label={__('Border height', 'development-mode-indicator')}
										value={settings.border.height}
										min={4}
										max={32}
										onChange={(value) => set(updateNested(settings, 'border', 'height', value))}
									/>
									<RangeControl
										label={__('Pattern size', 'development-mode-indicator')}
										value={settings.border.patternSize}
										min={8}
										max={80}
										onChange={(value) => set(updateNested(settings, 'border', 'patternSize', value))}
									/>
									<ColorSetting
										label={__('Primary color', 'development-mode-indicator')}
										value={settings.border.primaryColor}
										onChange={(value) => set(updateNested(settings, 'border', 'primaryColor', value))}
									/>
									<ColorSetting
										label={__('Secondary color', 'development-mode-indicator')}
										value={settings.border.secondaryColor}
										onChange={(value) => set(updateNested(settings, 'border', 'secondaryColor', value))}
									/>
								</>
							)}
						</CardBody>
					</Card>
				</aside>
			</main>
			<div className='dmi-footer'>
				<span>
					{settings.enabled
						? __('Your changes are ready to publish.', 'development-mode-indicator')
						: __('The indicator is currently hidden.', 'development-mode-indicator')}
				</span>
				<Button variant='primary' icon={check} onClick={save} isBusy={saving} disabled={saving}>
					{__('Save changes', 'development-mode-indicator')}
				</Button>
			</div>
		</div>
	);
};

export default App;
