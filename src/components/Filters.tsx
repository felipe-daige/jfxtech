import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { ChevronDown, ChevronUp, Filter } from 'lucide-react';

interface FiltersProps {
  activeCategory: string;
  setActiveCategory: (category: string) => void;
}

const filterSections = [
  {
    title: 'CATEGORIA',
    options: ['TODOS', 'MOUSES', 'TECLADOS', 'MONITORES', 'ÁUDIO', 'COMPONENTES', 'REFRIGERAÇÃO']
  },
  {
    title: 'TAXA DE ATUALIZAÇÃO (HZ)',
    options: ['1000Hz', '4000Hz', '8000Hz']
  },
  {
    title: 'TIPO DE PAINEL',
    options: ['IPS', 'TN', 'VA', 'OLED', 'QD-OLED']
  },
  {
    title: 'TIPO DE SWITCH',
    options: ['Mecânico', 'Óptico', 'Magnético Hall Effect']
  }
];

export default function Filters({ activeCategory, setActiveCategory }: FiltersProps) {
  const [openSections, setOpenSections] = useState<Record<string, boolean>>({
    'CATEGORIA': true,
    'TAXA DE ATUALIZAÇÃO (HZ)': true
  });

  const toggleSection = (title: string) => {
    setOpenSections(prev => ({ ...prev, [title]: !prev[title] }));
  };

  return (
    <div className="bg-white border border-[var(--color-lab-border)] rounded-md shadow-sm p-6 sticky top-24">
      <div className="flex items-center gap-2 mb-6 pb-4 border-b border-[var(--color-lab-border)]">
        <Filter className="w-4 h-4" />
        <h3 className="font-bold tracking-widest uppercase text-sm">ESPECIFICAÇÕES TÉCNICAS</h3>
      </div>

      <div className="space-y-6">
        {filterSections.map((section) => (
          <div key={section.title} className="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
            <button 
              onClick={() => toggleSection(section.title)}
              className="flex items-center justify-between w-full text-left font-mono text-xs font-bold text-gray-500 hover:text-black transition-colors uppercase tracking-widest mb-3"
            >
              {section.title}
              {openSections[section.title] ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
            </button>

            <AnimatePresence>
              {openSections[section.title] && (
                <motion.div 
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: 'auto', opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  className="overflow-hidden"
                >
                  <div className="space-y-2 pt-1">
                    {section.options.map((option) => {
                      const isActive = section.title === 'CATEGORIA' && activeCategory === option;
                      return (
                        <label key={option} className="flex items-center gap-3 cursor-pointer group">
                          <div className={`w-4 h-4 border rounded-sm flex items-center justify-center transition-colors ${isActive ? 'bg-black border-black' : 'border-gray-300 group-hover:border-gray-500'}`}>
                            {isActive && <div className="w-2 h-2 bg-white rounded-sm" />}
                          </div>
                          <span className={`text-sm transition-colors ${isActive ? 'font-bold text-black' : 'text-gray-600 group-hover:text-black'}`}>
                            {option}
                          </span>
                        </label>
                      );
                    })}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        ))}
      </div>

      <button className="w-full mt-8 bg-gray-50 border border-gray-200 text-gray-600 font-mono text-xs font-bold py-3 uppercase tracking-widest hover:bg-gray-100 hover:text-black transition-colors rounded-sm">
        LIMPAR FILTROS
      </button>
    </div>
  );
}
