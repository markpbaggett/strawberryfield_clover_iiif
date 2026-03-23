/**
 * Entry point for the Clover IIIF browser bundle.
 *
 * Exposes window.CloverViewer = { Viewer, React, ReactDOM } so that
 * clover_strawberry.js can mount the React app without any module system.
 */
import React from 'react';
import ReactDOM from 'react-dom/client';
import Viewer from '@samvera/clover-iiif/viewer';

window.CloverViewer = { Viewer, React, ReactDOM };
