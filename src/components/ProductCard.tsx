import React from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'motion/react';
import { ShoppingCart, Eye } from 'lucide-react';

interface ProductCardProps {
  product: {
    id: number;
    name: string;
    category: string;
    price: number;
    image: string;
    specs: Record<string, string>;
  };
}

export default function ProductCard({ product }: ProductCardProps) {
  return (
    <div className="group relative bg-white border border-[var(--color-lab-border)] rounded-md overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 flex flex-col h-full">
      {/* Category Tag */}
      <div className="absolute top-4 left-4 z-10 bg-black text-white text-[9px] font-mono px-2 py-1 uppercase tracking-widest rounded-sm">
        {product.category}
      </div>

      {/* Image Container */}
      <div className="relative aspect-square bg-[var(--color-lab-bg)] overflow-hidden flex items-center justify-center p-6">
        <motion.img 
          whileHover={{ scale: 1.05 }}
          transition={{ duration: 0.4 }}
          src={product.image} 
          alt={product.name} 
          className="w-full h-full object-contain mix-blend-multiply"
          referrerPolicy="no-referrer"
        />
        
        {/* Hover Actions */}
        <div className="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 backdrop-blur-[2px]">
          <Link 
            to={`/product/${product.id}`}
            className="w-10 h-10 bg-white rounded-full flex items-center justify-center text-black hover:bg-black hover:text-white transition-colors shadow-sm"
          >
            <Eye className="w-4 h-4" />
          </Link>
          <button className="w-10 h-10 bg-black rounded-full flex items-center justify-center text-white hover:bg-gray-800 transition-colors shadow-sm">
            <ShoppingCart className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="p-5 flex flex-col flex-1">
        <Link to={`/product/${product.id}`} className="block mb-2">
          <h3 className="font-bold text-lg tracking-tight group-hover:text-gray-600 transition-colors line-clamp-1">
            {product.name}
          </h3>
        </Link>
        
        {/* Quick Specs Grid */}
        <div className="grid grid-cols-2 gap-2 mb-4 mt-auto">
          {Object.entries(product.specs).slice(0, 4).map(([key, value]) => (
            <div key={key} className="bg-gray-50 px-2 py-1.5 rounded border border-gray-100">
              <p className="text-[9px] font-mono text-gray-400 uppercase tracking-wider mb-0.5">{key}</p>
              <p className="text-xs font-medium text-gray-800 truncate" title={value}>{value}</p>
            </div>
          ))}
        </div>

        {/* Price & Action */}
        <div className="flex items-center justify-between pt-4 border-t border-[var(--color-lab-border)]">
          <span className="font-mono font-bold text-lg">${product.price.toFixed(2)}</span>
          <Link 
            to={`/product/${product.id}`}
            className="text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-black transition-colors flex items-center gap-1"
          >
            DETALHES <ChevronRightIcon className="w-3 h-3" />
          </Link>
        </div>
      </div>
    </div>
  );
}

function ChevronRightIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="m9 18 6-6-6-6"/>
    </svg>
  );
}
