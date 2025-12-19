/**
 * Extracts error message from API error response
 * @param err - The error object from catch block
 * @param defaultMessage - Default message if error can't be extracted
 * @returns Error message string
 */
export const getErrorMessage = (err: unknown, defaultMessage: string): string => {
  if (
    err &&
    typeof err === 'object' &&
    'response' in err &&
    err.response &&
    typeof err.response === 'object' &&
    'data' in err.response &&
    err.response.data &&
    typeof err.response.data === 'object' &&
    'error' in err.response.data &&
    typeof err.response.data.error === 'string'
  ) {
    return err.response.data.error;
  }
  return defaultMessage;
};

