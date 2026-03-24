/**
 * Entry point for the Clover IIIF browser bundle.
 *
 * Exposes window.CloverViewer = { Viewer, Scroll, Slider, React, ReactDOM }
 * so that behavior JS files can mount any component without a module system.
 */
import React from 'react';
import ReactDOM from 'react-dom/client';
import Viewer from '@samvera/clover-iiif/viewer';
import Scroll from '@samvera/clover-iiif/scroll';
import Slider from '@samvera/clover-iiif/slider';

window.CloverViewer = { Viewer, Scroll, Slider, React, ReactDOM };
