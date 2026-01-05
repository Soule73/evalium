import type { Preview } from '@storybook/react';
import type { Decorator } from '@storybook/react';
import React, { useEffect } from 'react';
import '../../../../../resources/css/app.css';

const withDarkMode: Decorator = (Story, context) => {
  const backgroundColor = context.globals.backgrounds?.value;
  
  useEffect(() => {
    const isDark = backgroundColor === '#333333' || backgroundColor === '#333';
    
    if (isDark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }, [backgroundColor]);

  return <Story />;
};

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
    backgrounds: {
      default: 'light',
      values: [
        {
          name: 'light',
          value: '#F8F8F8',
        },
        {
          name: 'dark',
          value: '#333333',
        },
      ],
    },
  },
  decorators: [withDarkMode],
};

export default preview;
