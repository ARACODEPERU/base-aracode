export const healthServiceTypes = [
    { value: 'general', label: 'General', group: 'Medica', dental: false },
    { value: 'medicina_general', label: 'Medicina general', group: 'Medica', dental: false },
    { value: 'medicina_interna', label: 'Medicina interna', group: 'Medica', dental: false },
    { value: 'pediatria', label: 'Pediatria', group: 'Medica', dental: false },
    { value: 'ginecologia', label: 'Ginecologia', group: 'Medica', dental: false },
    { value: 'cardiologia', label: 'Cardiologia', group: 'Medica', dental: false },
    { value: 'dermatologia', label: 'Dermatologia', group: 'Medica', dental: false },
    { value: 'traumatologia', label: 'Traumatologia', group: 'Medica', dental: false },
    { value: 'neurologia', label: 'Neurologia', group: 'Medica', dental: false },
    { value: 'oftalmologia', label: 'Oftalmologia', group: 'Medica', dental: false },
    { value: 'otorrinolaringologia', label: 'Otorrinolaringologia', group: 'Medica', dental: false },
    { value: 'gastroenterologia', label: 'Gastroenterologia', group: 'Medica', dental: false },
    { value: 'endocrinologia', label: 'Endocrinologia', group: 'Medica', dental: false },
    { value: 'urologia', label: 'Urologia', group: 'Medica', dental: false },
    { value: 'psicologia', label: 'Psicologia', group: 'Medica', dental: false },
    { value: 'nutricion', label: 'Nutricion', group: 'Medica', dental: false },
    { value: 'odontologia_general', label: 'Odontologia general', group: 'Odontologica', dental: true },
    { value: 'ortodoncia', label: 'Ortodoncia', group: 'Odontologica', dental: true },
    { value: 'endodoncia', label: 'Endodoncia', group: 'Odontologica', dental: true },
    { value: 'periodoncia', label: 'Periodoncia', group: 'Odontologica', dental: true },
    { value: 'rehabilitacion_oral', label: 'Rehabilitacion oral', group: 'Odontologica', dental: true },
    { value: 'cirugia_bucal', label: 'Cirugia bucal', group: 'Odontologica', dental: true },
    { value: 'odontopediatria', label: 'Odontopediatria', group: 'Odontologica', dental: true },
    { value: 'implantologia', label: 'Implantologia', group: 'Odontologica', dental: true },
];

export const professionOptions = [
    'Médico cirujano',
    'Cirujano dentista',
    'Odontologo',
    'Psicologo',
    'Nutricionista',
    'Tecnólogo médico',
    'Enfermero',
];

export const serviceTypesByGroup = (group) => healthServiceTypes.filter((type) => type.group === group);

export const normalizeHealthServiceType = (serviceType) => serviceType === 'dental' ? 'odontologia_general' : serviceType;

export const serviceTypeOption = (serviceType) => {
    const normalizedServiceType = normalizeHealthServiceType(serviceType);

    return healthServiceTypes.find((type) => type.value === normalizedServiceType) || healthServiceTypes[0];
};

export const isDentalServiceType = (serviceType) => Boolean(serviceTypeOption(serviceType)?.dental);
