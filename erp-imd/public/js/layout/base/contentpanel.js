var contentPanel = {
		id: 'content-panel',
		region: 'center', // this is what makes this panel into a region within the containing layout
		layout: 'card',
		margins: '2 5 5 0',
		activeItem: 0,
		border: false,
		items: [
			// from basic.js:
			start, absolute, accordion, anchor, border, cardTabs, cardWizard, column, fit, form, table, vbox, hbox,
			// from custom.js:
			rowLayout, centerLayout,
			// from combination.js:
			absoluteForm, tabsNestedLayouts, windowForm
		]
	};