import { defineConfig } from 'vite';

export default defineConfig({
  define: {
    'process.env.NODE_ENV': '"production"',
    'process.env': '{}',
    'global': 'globalThis',
  },
  build: {
    lib: {
      entry: 'src/index.js',
      name: 'CloverViewer',
      fileName: 'clover-viewer',
      formats: ['iife'],
    },
    outDir: '../js',
    emptyOutDir: false,
    rollupOptions: {
      output: {
        inlineDynamicImports: true,
        entryFileNames: 'clover-viewer.bundle.js',
      },
    },
  },
  plugins: [
    // Strip "use client" directives that Rollup can't handle in library mode.
    {
      name: 'strip-use-client',
      transform(code) {
        return code.replace(/^\s*['"]use client['"]\s*;?\s*/m, '');
      },
    },
  ],
});
