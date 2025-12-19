/**
 * Parses availability string and provides helper functions
 * Supports formats like: "Mon-Fri 9AM-6PM", "Mon-Sat 8AM-7PM", etc.
 */

interface AvailabilityInfo {
  days: number[]; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
  startHour: number; // 0-23
  startMinute: number; // 0-59
  endHour: number; // 0-23
  endMinute: number; // 0-59
}

const DAY_MAP: Record<string, number> = {
  sun: 0,
  mon: 1,
  tue: 2,
  wed: 3,
  thu: 4,
  fri: 5,
  sat: 6,
};

/**
 * Parse availability string into structured data
 */
export const parseAvailability = (availability: string): AvailabilityInfo | null => {
  if (!availability) return null;

  try {
    // Parse format: "Mon-Fri 9AM-6PM" or "Mon-Sat 8AM-7PM"
    const parts = availability.trim().split(/\s+/);
    if (parts.length < 2) return null;

    const dayRange = parts[0].toLowerCase(); // "mon-fri"
    const timeRange = parts[1].toLowerCase(); // "9am-6pm"

    // Parse days
    const [startDay, endDay] = dayRange.split('-');
    const startDayNum = DAY_MAP[startDay.slice(0, 3)];
    const endDayNum = DAY_MAP[endDay.slice(0, 3)];

    if (startDayNum === undefined || endDayNum === undefined) return null;

    const days: number[] = [];
    for (let i = startDayNum; i <= endDayNum; i++) {
      days.push(i);
    }

    // Parse time range
    const [startTime, endTime] = timeRange.split('-');
    const start = parseTime(startTime);
    const end = parseTime(endTime);

    if (!start || !end) return null;

    return {
      days,
      startHour: start.hour,
      startMinute: start.minute,
      endHour: end.hour,
      endMinute: end.minute,
    };
  } catch {
    return null;
  }
};

/**
 * Parse time string like "9am", "6pm", "14:30"
 */
const parseTime = (timeStr: string): { hour: number; minute: number } | null => {
  const trimmed = timeStr.trim();
  
  // Handle 12-hour format: "9am", "6pm"
  const ampmMatch = trimmed.match(/(\d+)(am|pm)/i);
  if (ampmMatch) {
    let hour = parseInt(ampmMatch[1]);
    const ampm = ampmMatch[2].toLowerCase();
    
    if (ampm === 'pm' && hour !== 12) hour += 12;
    if (ampm === 'am' && hour === 12) hour = 0;
    
    return { hour, minute: 0 };
  }

  // Handle 24-hour format: "14:30", "09:00"
  const timeMatch = trimmed.match(/(\d+):(\d+)/);
  if (timeMatch) {
    return {
      hour: parseInt(timeMatch[1]),
      minute: parseInt(timeMatch[2]),
    };
  }

  return null;
};

/**
 * Check if a date is available based on availability info
 */
export const isDateAvailable = (date: Date, availability: AvailabilityInfo | null): boolean => {
  if (!availability) return true; // If no availability info, allow all dates

  const dayOfWeek = date.getDay();
  return availability.days.includes(dayOfWeek);
};

/**
 * Check if a time is within available hours
 */
export const isTimeAvailable = (
  hour: number,
  minute: number,
  availability: AvailabilityInfo | null
): boolean => {
  if (!availability) return true;

  const requestedMinutes = hour * 60 + minute;
  const startMinutes = availability.startHour * 60 + availability.startMinute;
  const endMinutes = availability.endHour * 60 + availability.endMinute;

  return requestedMinutes >= startMinutes && requestedMinutes <= endMinutes;
};

/**
 * Get available time slots for a given date
 */
export const getAvailableTimeSlots = (
  date: Date,
  availability: AvailabilityInfo | null,
  intervalMinutes: number = 30
): string[] => {
  if (!availability || !isDateAvailable(date, availability)) {
    return [];
  }

  const slots: string[] = [];
  const startMinutes = availability.startHour * 60 + availability.startMinute;
  const endMinutes = availability.endHour * 60 + availability.endMinute;

  for (let minutes = startMinutes; minutes <= endMinutes; minutes += intervalMinutes) {
    const hour = Math.floor(minutes / 60);
    const minute = minutes % 60;
    const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
    slots.push(timeStr);
  }

  return slots;
};

/**
 * Get the next available date based on availability
 */
export const getNextAvailableDate = (availability: AvailabilityInfo | null): Date => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (!availability) {
    return today;
  }

  // Find next available day
  for (let i = 0; i < 7; i++) {
    const checkDate = new Date(today);
    checkDate.setDate(today.getDate() + i);
    
    if (isDateAvailable(checkDate, availability)) {
      return checkDate;
    }
  }

  return today; // Fallback
};

/**
 * Validate if selected date and time are available
 */
export const validateBookingTime = (
  date: string,
  time: string,
  availability: AvailabilityInfo | null
): { valid: boolean; message?: string } => {
  if (!availability) {
    return { valid: true };
  }

  const selectedDate = new Date(date);
  const [hour, minute] = time.split(':').map(Number);

  if (!isDateAvailable(selectedDate, availability)) {
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const selectedDayName = dayNames[selectedDate.getDay()];
    return {
      valid: false,
      message: `This provider is not available on ${selectedDayName}. Please select an available day.`,
    };
  }

  if (!isTimeAvailable(hour, minute, availability)) {
    const startTime = formatTime(availability.startHour, availability.startMinute);
    const endTime = formatTime(availability.endHour, availability.endMinute);
    return {
      valid: false,
      message: `Time must be between ${startTime} and ${endTime}.`,
    };
  }

  return { valid: true };
};

/**
 * Format time for display
 */
const formatTime = (hour: number, minute: number): string => {
  const period = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
  return `${displayHour}:${minute.toString().padStart(2, '0')} ${period}`;
};

