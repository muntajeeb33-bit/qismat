export const RELIGIONS = [
  'Hindu', 'Muslim', 'Christian', 'Sikh', 'Jain', 'Buddhist', 'Parsi', 'Jewish',
  "Baha'i", 'Spiritual', 'No religion', 'Other', 'Prefer not to say',
];

export const DENOMINATIONS = [
  'Sunni', 'Shia', 'Ahmadiyya', 'Bohra', 'Dawoodi Bohra', 'Khoja', 'Mappila',
  'Catholic', 'Protestant', 'Orthodox', 'Syrian Christian', 'Pentecostal',
  'Anglican', 'Evangelical', 'Digambar', 'Shwetambar', 'Sthanakvasi',
];

export const COMMUNITIES = [
  'Agarwal', 'Arora', 'Brahmin', 'Gupta', 'Iyengar', 'Iyer', 'Jat', 'Kayastha',
  'Khatri', 'Maratha', 'Nair', 'Rajput', 'Ramgarhia', 'Thakur', 'Vellalar',
  'Vishwakarma', 'Yadav',
];

export const ETHNICITIES = [
  'South Asian', 'Middle Eastern or North African', 'Black, African or Caribbean',
  'East Asian', 'Southeast Asian', 'Central Asian', 'White or European',
  'Hispanic or Latino', 'Indigenous', 'Mixed or multiracial', 'Other',
  'Prefer not to say',
];

export function SuggestionList({ id, values }) {
  return <datalist id={id}>{values.map((value) => <option value={value} key={value} />)}</datalist>;
}
